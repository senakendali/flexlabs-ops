@extends('layouts.app-dashboard')

@section('title', 'Instructor Schedules')

@section('content')
@php
    $scheduleCollection = $schedules->getCollection();
    $summaryScheduled = $scheduleCollection->where('status', 'scheduled')->count();
    $summaryCompleted = $scheduleCollection->where('status', 'completed')->count();
    $summaryMakeup = $scheduleCollection->where('is_makeup_session', true)->count();
    $summaryReplacement = $scheduleCollection->filter(fn ($item) => !empty($item->replacement_instructor_id))->count();
@endphp

<div class="container-fluid px-4 py-4 instructor-schedules-index-page">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic</div>
                <h1 class="page-title mb-2">Instructor Schedule List</h1>
                <p class="page-subtitle mb-0">
                    Daftar jadwal instruktur berdasarkan tanggal, batch, metode pembelajaran, status sesi, dan kebutuhan penggantian pengajar.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('instructor-schedules.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Add Schedule
                </a>
            </div>
        </div>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 1090;"
    ></div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-calendar3"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Schedules</div>
                        <div class="stat-value">{{ $schedules->total() }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Jumlah seluruh jadwal instruktur yang tersedia sesuai hasil pencarian saat ini.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <div class="stat-title">Scheduled</div>
                        <div class="stat-value">{{ $summaryScheduled }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Sesi yang sudah dijadwalkan dan menunggu pelaksanaan.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                    <div>
                        <div class="stat-title">Makeup Sessions</div>
                        <div class="stat-value">{{ $summaryMakeup }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Sesi susulan yang dibuat untuk pengganti jadwal sebelumnya.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Replacement Instructor</div>
                        <div class="stat-value">{{ $summaryReplacement }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Sesi yang dijalankan dengan instruktur pengganti.
                </div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Schedules</h5>
                <p class="content-card-subtitle mb-0">
                    Gunakan pencarian dan filter berikut untuk menelusuri jadwal berdasarkan instruktur, batch, program, tanggal, dan status sesi.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('instructor-schedules.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <label class="form-label">Search</label>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ $filters['search'] ?? '' }}"
                            placeholder="Search session title, location, link, or notes"
                        >
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <label class="form-label">Instructor</label>
                        <select name="instructor_id" class="form-select">
                            <option value="">All Instructors</option>
                            @foreach ($instructors as $instructor)
                                <option value="{{ $instructor->id }}" @selected(($filters['instructor_id'] ?? '') == $instructor->id)>
                                    {{ $instructor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <label class="form-label">Replacement</label>
                        <select name="replacement_instructor_id" class="form-select">
                            <option value="">All Replacement</option>
                            @foreach ($instructors as $instructor)
                                <option value="{{ $instructor->id }}" @selected(($filters['replacement_instructor_id'] ?? '') == $instructor->id)>
                                    {{ $instructor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <label class="form-label">Batch</label>
                        <select name="batch_id" class="form-select">
                            <option value="">All Batches</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch->id }}" @selected(($filters['batch_id'] ?? '') == $batch->id)>
                                    {{ $batch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <label class="form-label">Program</label>
                        <select name="program_id" class="form-select">
                            <option value="">All Programs</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}" @selected(($filters['program_id'] ?? '') == $program->id)>
                                    {{ $program->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-1 col-lg-4 col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <label class="form-label">Delivery Mode</label>
                        <select name="delivery_mode" class="form-select">
                            <option value="">All Modes</option>
                            @foreach ($deliveryModeOptions as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['delivery_mode'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <label class="form-label">Session Type</label>
                        <select name="is_makeup_session" class="form-select">
                            <option value="">All Types</option>
                            <option value="0" @selected(($filters['is_makeup_session'] ?? '') === '0' || ($filters['is_makeup_session'] ?? null) === 0)>Regular Session</option>
                            <option value="1" @selected(($filters['is_makeup_session'] ?? '') === '1' || ($filters['is_makeup_session'] ?? null) === 1)>Makeup Session</option>
                        </select>
                    </div>

                    <div class="col-xl-1 col-md-6">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                    </div>

                    <div class="col-xl-1 col-md-6">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                    </div>

                    <div class="col-xl-1 col-md-6">
                        <label class="form-label">Rows</label>
                        <select name="per_page" class="form-select">
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected(($filters['per_page'] ?? 10) == $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-12">
                        <div class="filter-action-row d-flex gap-2 flex-wrap justify-content-lg-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel me-1"></i> Apply Filter
                            </button>

                            <a href="{{ route('instructor-schedules.index') }}" class="btn btn-light border">
                                <i class="bi bi-arrow-clockwise me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Instructor Schedules</h5>
                <p class="content-card-subtitle mb-0">
                    Setiap jadwal menampilkan sesi, instruktur utama atau pengganti, batch, metode pembelajaran, dan status pelaksanaan.
                </p>
            </div>

            <div class="table-meta-info">
                Total: <strong>{{ $schedules->total() }}</strong> schedules
            </div>
        </div>

        <div class="content-card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 60px;">#</th>
                            <th>Session</th>
                            <th>Instructor</th>
                            <th>Schedule</th>
                            <th>Class</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th class="text-center pe-4" style="width: 170px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($schedules as $index => $schedule)
                            @php
                                $statusClass = match($schedule->status) {
                                    'completed' => 'bg-success-subtle text-success-emphasis',
                                    'cancelled' => 'bg-danger-subtle text-danger-emphasis',
                                    'rescheduled' => 'bg-warning-subtle text-warning-emphasis',
                                    default => 'bg-primary-subtle text-primary-emphasis',
                                };
                            @endphp
                            <tr>
                                <td class="ps-4">{{ $schedules->firstItem() + $index }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $schedule->session_title }}</div>
                                    @if ($schedule->subTopic)
                                        <div class="text-muted small mt-1">{{ $schedule->subTopic->name }}</div>
                                    @endif
                                    @if ($schedule->notes)
                                        <div class="text-muted small mt-1 text-truncate-2">{{ $schedule->notes }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $schedule->instructor->name ?? '-' }}</div>
                                    @if ($schedule->replacementInstructor)
                                        <div class="text-muted small mt-1">
                                            Replacement · {{ $schedule->replacementInstructor->name }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $schedule->schedule_date_label ?? optional($schedule->schedule_date)->format('d M Y') }}</div>
                                    <div class="text-muted small mt-1">{{ $schedule->time_range ?? '-' }}</div>
                                    <div class="mt-2">
                                        <span class="badge rounded-pill bg-light text-dark border">
                                            {{ $schedule->delivery_mode_label ?? ucfirst($schedule->delivery_mode) }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $schedule->batch->name ?? '-' }}</div>
                                    <div class="text-muted small mt-1">{{ $schedule->program->name ?? '-' }}</div>
                                    @if ($schedule->rescheduledFrom)
                                        <div class="text-muted small mt-1">
                                            From · {{ $schedule->rescheduledFrom->session_title }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge rounded-pill {{ $statusClass }}">
                                        {{ $schedule->status_label ?? ucfirst($schedule->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($schedule->is_makeup_session)
                                        <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">
                                            Makeup Session
                                        </span>
                                    @else
                                        <span class="badge rounded-pill bg-light text-dark border">
                                            Regular Session
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center pe-4">
                                    <div class="d-inline-flex gap-2">
                                        <a
                                            href="{{ route('instructor-schedules.show', $schedule) }}"
                                            class="btn btn-sm btn-outline-secondary"
                                            title="View"
                                        >
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <a
                                            href="{{ route('instructor-schedules.edit', $schedule) }}"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Edit"
                                        >
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger delete-schedule-btn"
                                            data-url="{{ route('instructor-schedules.destroy', $schedule) }}"
                                            data-title="{{ $schedule->session_title }}"
                                            title="Delete"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="empty-state-box mx-4 my-3">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-calendar2-x"></i>
                                        </div>
                                        <div class="empty-state-title">No instructor schedules found</div>
                                        <div class="empty-state-subtitle">
                                            Belum ada jadwal instruktur yang sesuai dengan filter saat ini.
                                        </div>
                                        <div class="mt-3">
                                            <a href="{{ route('instructor-schedules.create') }}" class="btn btn-primary">
                                                <i class="bi bi-plus-circle me-1"></i> Create Schedule
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($schedules->hasPages())
            <div class="content-card-footer d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div class="table-meta-info">
                    Menampilkan <strong>{{ $schedules->firstItem() }}</strong> - <strong>{{ $schedules->lastItem() }}</strong>
                    dari <strong>{{ $schedules->total() }}</strong> schedules
                </div>
                <div>
                    {{ $schedules->links() }}
                </div>
            </div>
        @endif
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
                <div class="delete-schedule-modal-box">
                    <div class="delete-schedule-modal-icon">
                        <i class="bi bi-trash3"></i>
                    </div>
                    <div>
                        <div class="fw-semibold text-dark mb-1">Yakin mau hapus jadwal ini?</div>
                        <div class="text-muted small">
                            Jadwal <span class="fw-semibold text-dark" id="deleteScheduleTitle">-</span> akan dihapus permanen.
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteScheduleBtn">
                    <i class="bi bi-trash me-1"></i> Delete Schedule
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .instructor-schedules-index-page .table-meta-info {
        font-size: .88rem;
        color: #6b7280;
    }

    .instructor-schedules-index-page .empty-state-box {
        padding: 28px 20px;
        border-radius: 18px;
        border: 1px dashed #d7dce3;
        text-align: center;
        background: #fcfcfd;
    }

    .instructor-schedules-index-page .empty-state-icon {
        width: 58px;
        height: 58px;
        margin: 0 auto 12px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #eee7fb;
        color: #5B3E8E;
        font-size: 1.4rem;
    }

    .instructor-schedules-index-page .empty-state-title {
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 4px;
    }

    .instructor-schedules-index-page .empty-state-subtitle {
        font-size: .85rem;
        color: #6b7280;
    }

    .instructor-schedules-index-page .content-card-footer {
        padding: 16px 20px;
        border-top: 1px solid #eef2f7;
        background: #fff;
    }

    .instructor-schedules-index-page .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .delete-schedule-modal-box {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 14px;
        border-radius: 16px;
        background: #fff5f5;
        border: 1px solid #ffd9d9;
    }

    .delete-schedule-modal-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fee2e2;
        color: #dc2626;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    .instructor-schedules-index-page .toast {
        min-width: 280px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }

    @media (max-width: 991.98px) {
        .instructor-schedules-index-page .content-card-body .btn {
            white-space: nowrap;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('deleteScheduleModal');
    const deleteScheduleTitle = document.getElementById('deleteScheduleTitle');
    const confirmDeleteScheduleBtn = document.getElementById('confirmDeleteScheduleBtn');
    const toastContainer = document.getElementById('toastContainer');

    if (!modalElement || !confirmDeleteScheduleBtn || typeof bootstrap === 'undefined') {
        return;
    }

    const deleteModal = new bootstrap.Modal(modalElement);

    let selectedDeleteUrl = null;
    let selectedDeleteTrigger = null;
    let reloadTimeout = null;

    function showToast(message, type = 'success') {
        if (!toastContainer) return;

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
        const toast = new bootstrap.Toast(toastEl, {
            delay: 1500
        });

        toast.show();

        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    }

    function resetDeleteState() {
        selectedDeleteUrl = null;
        selectedDeleteTrigger = null;
        confirmDeleteScheduleBtn.disabled = false;
        confirmDeleteScheduleBtn.innerHTML = '<i class="bi bi-trash me-1"></i> Delete Schedule';
    }

    function scheduleReload() {
        if (reloadTimeout) {
            clearTimeout(reloadTimeout);
        }

        reloadTimeout = setTimeout(function () {
            window.location.reload();
        }, 1200);
    }

    async function parseResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
            return await response.json();
        }

        const text = await response.text();

        return {
            success: false,
            message: text || 'Unexpected server response.',
        };
    }

    document.querySelectorAll('.delete-schedule-btn').forEach((button) => {
        button.addEventListener('click', function () {
            selectedDeleteUrl = this.dataset.url;
            selectedDeleteTrigger = this;

            deleteScheduleTitle.textContent = this.dataset.title || 'this schedule';
            deleteModal.show();
        });
    });

    modalElement.addEventListener('hidden.bs.modal', function () {
        resetDeleteState();
    });

    confirmDeleteScheduleBtn.addEventListener('click', async function () {
        if (!selectedDeleteUrl) {
            return;
        }

        confirmDeleteScheduleBtn.disabled = true;
        confirmDeleteScheduleBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Deleting...';

        if (selectedDeleteTrigger) {
            selectedDeleteTrigger.disabled = true;
        }

        try {
            const response = await fetch(selectedDeleteUrl, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const result = await parseResponse(response);

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete schedule.');
            }

            deleteModal.hide();
            showToast(result.message || 'Schedule deleted successfully.', 'success');
            scheduleReload();
        } catch (error) {
            showToast(error.message || 'Failed to delete schedule.', 'danger');

            confirmDeleteScheduleBtn.disabled = false;
            confirmDeleteScheduleBtn.innerHTML = '<i class="bi bi-trash me-1"></i> Delete Schedule';

            if (selectedDeleteTrigger) {
                selectedDeleteTrigger.disabled = false;
            }
        }
    });
});
</script>
@endpush
