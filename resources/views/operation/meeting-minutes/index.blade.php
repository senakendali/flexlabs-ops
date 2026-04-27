@extends('layouts.app-dashboard')

@section('title', 'Meeting Minutes / MOM')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Operations</div>
                <h1 class="page-title mb-2">Meeting Minutes / MOM</h1>
                <p class="page-subtitle mb-0">
                    Kelola catatan meeting, peserta, agenda, dan <strong>action items</strong> untuk follow-up operasional.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('operation.meeting-minutes.create') }}" class="btn btn-primary btn-modern">
                    <i class="bi bi-plus-circle me-2"></i>Create MOM
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total MOM</div>
                        <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Total meeting minutes yang tersimpan.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div>
                        <div class="stat-title">Draft</div>
                        <div class="stat-value">{{ $stats['draft'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">MOM yang masih dalam proses penyusunan.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Completed</div>
                        <div class="stat-value">{{ $stats['completed'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">MOM yang sudah selesai/final.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-list-task"></i>
                    </div>
                    <div>
                        <div class="stat-title">Pending Actions</div>
                        <div class="stat-value">{{ $stats['pending_action_items'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Action item yang masih perlu follow-up.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter MOM</h5>
                <p class="content-card-subtitle mb-0">
                    Cari MOM berdasarkan judul, nomor, project, tipe meeting, department, status, tanggal, atau organizer.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('operation.meeting-minutes.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Keyword</label>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ $search ?? request('search') }}"
                            placeholder="Cari judul / MOM no / project..."
                        >
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <label class="form-label">Meeting Type</label>
                        <select name="meeting_type" class="form-select">
                            <option value="">All Types</option>
                            @foreach ([
                                'internal' => 'Internal',
                                'client' => 'Client',
                                'vendor' => 'Vendor',
                                'academic' => 'Academic',
                                'marketing' => 'Marketing',
                                'finance' => 'Finance',
                                'operation' => 'Operation',
                                'other' => 'Other',
                            ] as $key => $label)
                                <option value="{{ $key }}" {{ ($meetingType ?? request('meeting_type')) === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-select">
                            <option value="">All Departments</option>
                            @foreach ([
                                'operation' => 'Operation',
                                'academic' => 'Academic',
                                'sales' => 'Sales',
                                'marketing' => 'Marketing',
                                'finance' => 'Finance',
                                'management' => 'Management',
                                'general_affair' => 'General Affair',
                                'other' => 'Other',
                            ] as $key => $label)
                                <option value="{{ $key }}" {{ ($department ?? request('department')) === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            @foreach ([
                                'draft' => 'Draft',
                                'published' => 'Published',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ] as $key => $label)
                                <option value="{{ $key }}" {{ ($status ?? request('status')) === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Organizer</label>
                        <select name="organizer_id" class="form-select">
                            <option value="">All Organizers</option>
                            @foreach ($users ?? [] as $user)
                                <option value="{{ $user->id }}" {{ (string) ($organizerId ?? request('organizer_id')) === (string) $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} — {{ $user->email }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <label class="form-label">Date From</label>
                        <input
                            type="date"
                            name="date_from"
                            class="form-control"
                            value="{{ $dateFrom ?? request('date_from') }}"
                        >
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <label class="form-label">Date To</label>
                        <input
                            type="date"
                            name="date_to"
                            class="form-control"
                            value="{{ $dateTo ?? request('date_to') }}"
                        >
                    </div>

                    <div class="col-xl-8">
                        <div class="d-flex gap-2 justify-content-xl-end flex-wrap">
                            <a href="{{ route('operation.meeting-minutes.index') }}" class="btn btn-light border">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                            </a>

                            <button type="submit" class="btn btn-primary btn-modern">
                                <i class="bi bi-funnel me-1"></i> Filter
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">MOM List</h5>
                <p class="content-card-subtitle mb-0">
                    Daftar catatan meeting dan progress follow-up action item.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if(($meetingMinutes ?? collect())->count())
                <div class="table-responsive">
                    <table class="table align-middle custom-table mb-0">
                        <thead>
                            <tr>
                                <th>MOM</th>
                                <th>Date & Time</th>
                                <th>Type</th>
                                <th>Organizer</th>
                                <th>Action Items</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($meetingMinutes as $meeting)
                                @php
                                    $statusClass = match($meeting->status) {
                                        'published' => 'bg-info-subtle text-info-emphasis',
                                        'completed' => 'bg-success-subtle text-success-emphasis',
                                        'cancelled' => 'bg-danger-subtle text-danger-emphasis',
                                        default => 'bg-warning-subtle text-warning-emphasis',
                                    };

                                    $typeClass = match($meeting->meeting_type) {
                                        'client' => 'bg-success-subtle text-success-emphasis',
                                        'vendor' => 'bg-warning-subtle text-warning-emphasis',
                                        'academic' => 'bg-primary-subtle text-primary-emphasis',
                                        'marketing' => 'bg-danger-subtle text-danger-emphasis',
                                        'finance' => 'bg-success-subtle text-success-emphasis',
                                        'operation' => 'bg-secondary-subtle text-secondary-emphasis',
                                        default => 'bg-light text-dark border',
                                    };

                                    $dateLabel = $meeting->meeting_date
                                        ? $meeting->meeting_date->format('d M Y')
                                        : '-';

                                    $timeLabel = ($meeting->start_time ? substr((string) $meeting->start_time, 0, 5) : '-')
                                        . ' - '
                                        . ($meeting->end_time ? substr((string) $meeting->end_time, 0, 5) : '-');

                                    $pendingCount = $meeting->pending_action_items_count ?? 0;
                                    $totalActionCount = $meeting->action_items_count ?? 0;
                                @endphp

                                <tr id="meeting-row-{{ $meeting->id }}">
                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $meeting->title }}
                                        </div>

                                        <div class="text-muted small mt-1">
                                            {{ $meeting->meeting_no }}
                                        </div>

                                        @if($meeting->related_project)
                                            <div class="mt-2">
                                                <span class="badge rounded-pill bg-light text-dark border">
                                                    <i class="bi bi-folder2-open me-1"></i>{{ $meeting->related_project }}
                                                </span>
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $dateLabel }}
                                        </div>

                                        <div class="text-muted small mt-1">
                                            <i class="bi bi-clock me-1"></i>{{ $timeLabel }}
                                        </div>

                                        @if($meeting->location || $meeting->platform)
                                            <div class="text-muted small mt-1">
                                                <i class="bi bi-geo-alt me-1"></i>{{ $meeting->location ?: $meeting->platform }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge rounded-pill {{ $typeClass }}">
                                            {{ ucwords(str_replace('_', ' ', $meeting->meeting_type)) }}
                                        </span>

                                        @if($meeting->department)
                                            <div class="text-muted small mt-2">
                                                {{ ucwords(str_replace('_', ' ', $meeting->department)) }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $meeting->organizer?->name ?? '-' }}
                                        </div>

                                        <div class="text-muted small mt-1">
                                            {{ $meeting->participants_count ?? 0 }} participants
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $pendingCount }} pending
                                        </div>

                                        <div class="text-muted small mt-1">
                                            {{ $totalActionCount }} total action items
                                        </div>

                                        @if($totalActionCount > 0)
                                            @php
                                                $doneCount = $meeting->completed_action_items_count ?? 0;
                                                $progressPercent = $totalActionCount > 0
                                                    ? round(($doneCount / $totalActionCount) * 100)
                                                    : 0;
                                            @endphp

                                            <div class="progress progress-modern mt-2" style="height: 8px;">
                                                <div class="progress-bar" style="width: {{ $progressPercent }}%"></div>
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge rounded-pill {{ $statusClass }}">
                                            {{ ucwords(str_replace('_', ' ', $meeting->status)) }}
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                                            <a href="{{ route('operation.meeting-minutes.show', $meeting) }}" class="btn btn-sm btn-light border">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <a href="{{ route('operation.meeting-minutes.download-pdf', $meeting) }}" class="btn btn-sm btn-light border">
                                                <i class="bi bi-download"></i>
                                            </a>

                                            <a href="{{ route('operation.meeting-minutes.edit', $meeting) }}" class="btn btn-sm btn-light border">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-light border text-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteConfirmModal"
                                                data-delete-name="{{ $meeting->title }}"
                                                data-delete-url="{{ route('operation.meeting-minutes.destroy', $meeting) }}"
                                                data-delete-id="{{ $meeting->id }}"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($meetingMinutes->hasPages())
                    <div class="mt-4">
                        {{ $meetingMinutes->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-journal-x"></i>
                    </div>

                    <div class="empty-state-title">MOM belum tersedia</div>

                    <div class="empty-state-subtitle mb-3">
                        Belum ada meeting minutes yang dibuat. Mulai dengan membuat MOM untuk meeting internal, client, vendor, atau operasional.
                    </div>

                    <a href="{{ route('operation.meeting-minutes.create') }}" class="btn btn-primary btn-modern">
                        <i class="bi bi-plus-circle me-2"></i>Create First MOM
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal delete-confirm-modal">
            <div class="modal-header border-0 pb-0">
                <div class="delete-confirm-heading">
                    <div class="delete-confirm-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>

                    <div>
                        <h5 class="modal-title" id="deleteConfirmModalLabel">Delete MOM</h5>
                        <p class="text-muted mb-0">
                            Konfirmasi sebelum menghapus meeting minutes.
                        </p>
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="delete-confirm-message">
                    <div class="delete-confirm-label">MOM yang akan dihapus</div>
                    <div class="delete-confirm-name" id="deleteConfirmName">-</div>
                </div>

                <div class="delete-confirm-warning mt-3">
                    MOM yang sudah dihapus tidak bisa dikembalikan.
                    Peserta, agenda, dan action item di dalamnya juga akan ikut terhapus.
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button" class="btn btn-danger btn-modern" id="confirmDeleteBtn">
                    <i class="bi bi-trash me-2"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="appToast" class="toast align-items-center border-0 shadow-sm" role="alert">
        <div class="d-flex">
            <div class="toast-body">Success</div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    const deleteModal = document.getElementById('deleteConfirmModal');
    const deleteConfirmName = document.getElementById('deleteConfirmName');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    let deleteUrl = null;
    let deleteId = null;

    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('appToast');

        if (!toastEl) {
            alert(message);
            return;
        }

        const toastBody = toastEl.querySelector('.toast-body');
        toastBody.innerHTML = message;

        toastEl.classList.remove('bg-success', 'bg-danger', 'text-white');

        if (type === 'success') {
            toastEl.classList.add('bg-success', 'text-white');
        } else {
            toastEl.classList.add('bg-danger', 'text-white');
        }

        const toast = bootstrap.Toast.getOrCreateInstance(toastEl, {
            delay: 2500
        });

        toast.show();
    }

    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            deleteUrl = button?.getAttribute('data-delete-url');
            deleteId = button?.getAttribute('data-delete-id');

            const deleteName = button?.getAttribute('data-delete-name') || '-';

            if (deleteConfirmName) {
                deleteConfirmName.textContent = deleteName;
            }
        });
    }

    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', async function () {
            if (!deleteUrl) return;

            const originalHtml = confirmDeleteBtn.innerHTML;

            confirmDeleteBtn.disabled = true;
            confirmDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';

            try {
                const response = await fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Gagal menghapus MOM.');
                }

                if (deleteId) {
                    document.getElementById(`meeting-row-${deleteId}`)?.remove();
                }

                bootstrap.Modal.getInstance(deleteModal)?.hide();
                showToast(result.message || 'MOM berhasil dihapus.', 'success');
            } catch (error) {
                showToast(error.message || 'Terjadi kesalahan saat menghapus MOM.', 'danger');
            } finally {
                confirmDeleteBtn.disabled = false;
                confirmDeleteBtn.innerHTML = originalHtml;
            }
        });
    }
});
</script>
@endpush