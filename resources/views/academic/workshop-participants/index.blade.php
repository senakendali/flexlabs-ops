@extends('layouts.app-dashboard')

@section('title', 'Workshop Participants')

@section('content')
@php
    $totalParticipants = $participants->total();

    $confirmedCount = $participants->getCollection()
        ->whereIn('status', ['confirmed', 'attended'])
        ->count();

    $pendingCount = $participants->getCollection()
        ->where('status', 'pending_payment')
        ->count();

    $attendedCount = $participants->getCollection()
        ->where('status', 'attended')
        ->count();
@endphp

<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Workshop Operations</div>
                <h1 class="page-title mb-2">Workshop Participants</h1>
                <p class="page-subtitle mb-0">
                    Input peserta workshop, generate sales order, dan payment schedule dalam satu flow.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-primary btn-modern" onclick="openCreateModal()">
                    <i class="bi bi-plus-lg me-2"></i>Add Participant
                </button>
            </div>
        </div>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <div class="row g-3 mb-4">
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total</div>
                        <div class="stat-value">{{ $totalParticipants }}</div>
                    </div>
                </div>
                <div class="stat-description">Total peserta workshop terdaftar.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="stat-title">Pending</div>
                        <div class="stat-value">{{ $pendingCount }}</div>
                    </div>
                </div>
                <div class="stat-description">Peserta yang masih menunggu pembayaran.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Confirmed</div>
                        <div class="stat-value">{{ $confirmedCount }}</div>
                    </div>
                </div>
                <div class="stat-description">Peserta yang sudah confirmed / paid.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Attended</div>
                        <div class="stat-value">{{ $attendedCount }}</div>
                    </div>
                </div>
                <div class="stat-description">Peserta yang sudah hadir workshop.</div>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Participant List</h5>
                <p class="content-card-subtitle mb-0">
                    Kelola peserta workshop. Saat peserta ditambahkan, sistem otomatis membuat order dan payment schedule.
                </p>
            </div>

            <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                <select
                    name="workshop_id"
                    class="form-select form-select-sm"
                    style="width: 220px;"
                    onchange="this.form.submit()"
                >
                    <option value="">All Workshops</option>
                    @foreach ($workshops as $workshop)
                        <option value="{{ $workshop->id }}" {{ (string) request('workshop_id') === (string) $workshop->id ? 'selected' : '' }}>
                            {{ $workshop->title ?? $workshop->name ?? 'Workshop #' . $workshop->id }}
                        </option>
                    @endforeach
                </select>

                <select
                    name="status"
                    class="form-select form-select-sm"
                    style="width: 180px;"
                    onchange="this.form.submit()"
                >
                    <option value="">All Status</option>
                    @foreach (['registered', 'pending_payment', 'confirmed', 'attended', 'cancelled'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                            {{ str_replace('_', ' ', ucfirst($status)) }}
                        </option>
                    @endforeach
                </select>

                <div class="input-group input-group-sm" style="width: 260px;">
                    <input
                        type="text"
                        name="keyword"
                        class="form-control"
                        value="{{ request('keyword') }}"
                        placeholder="Search participant..."
                    >
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>

                @if(request()->hasAny(['workshop_id', 'status', 'keyword']))
                    <a href="{{ route('academic.workshop-participants.index') }}" class="btn btn-sm btn-outline-secondary">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <div class="content-card-body">
            @if($participants->count())
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 70px;">No</th>
                                <th class="text-nowrap">Participant</th>
                                <th class="text-nowrap">Workshop</th>
                                <th class="text-nowrap">Order</th>
                                <th class="text-nowrap">Payment Schedule</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-nowrap">Registered</th>
                                <th class="text-end text-nowrap" style="width: 160px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($participants as $participant)
                                @php
                                    $student = $participant->student;
                                    $workshop = $participant->workshop;
                                    $order = $participant->order;
                                    $schedule = $order?->paymentSchedules?->first();

                                    $studentName = $student?->full_name ?? '-';
                                    $workshopTitle = $workshop?->title ?? $workshop?->name ?? '-';

                                    $statusClass = match($participant->status) {
                                        'registered' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                        'pending_payment' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                        'confirmed' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
                                        'attended' => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
                                        'cancelled' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
                                        default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                    };

                                    $orderStatusClass = match($order?->status) {
                                        'paid' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
                                        'partial' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                        'pending' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                        'cancelled' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
                                        default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                    };

                                    $scheduleStatusClass = match($schedule?->status) {
                                        'paid' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
                                        'pending' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                        'overdue' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
                                        'cancelled' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                        default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                    };

                                    $editPayload = [
                                        'id' => $participant->id,
                                        'workshop_id' => $participant->workshop_id,
                                        'student_id' => $participant->student_id,
                                        'full_name' => $student?->full_name,
                                        'email' => $student?->email,
                                        'phone' => $student?->phone,
                                        'city' => $student?->city,
                                        'goal' => $student?->goal,
                                        'status' => $participant->status,
                                        'discount' => $order?->discount ?? 0,
                                        'due_date' => $schedule?->due_date ? \Illuminate\Support\Carbon::parse($schedule->due_date)->format('Y-m-d') : null,
                                        'notes' => $participant->notes,
                                        'payment_notes' => $schedule?->notes,
                                    ];
                                @endphp

                                <tr>
                                    <td class="text-muted">
                                        {{ ($participants->currentPage() - 1) * $participants->perPage() + $loop->iteration }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $studentName }}
                                        </div>

                                        <div class="small text-muted">
                                            {{ $student?->email ?: '-' }}
                                        </div>

                                        <div class="small text-muted">
                                            {{ $student?->phone ?: '-' }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $workshopTitle }}
                                        </div>

                                        <div class="small text-muted">
                                            {{ $workshop?->duration ?? '-' }}
                                            @if($workshop?->level)
                                                · {{ $workshop->level }}
                                            @endif
                                        </div>
                                    </td>

                                    <td>
                                        @if($order)
                                            <div class="fw-semibold text-dark">
                                                Rp {{ number_format($order->final_price ?? 0, 0, ',', '.') }}
                                            </div>

                                            <span class="badge rounded-pill {{ $orderStatusClass }}">
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($schedule)
                                            <div class="fw-semibold text-dark">
                                                Rp {{ number_format($schedule->amount ?? 0, 0, ',', '.') }}
                                            </div>

                                            <div class="small text-muted">
                                                Due: {{ $schedule->due_date ? \Illuminate\Support\Carbon::parse($schedule->due_date)->format('d M Y') : '-' }}
                                            </div>

                                            <span class="badge rounded-pill {{ $scheduleStatusClass }}">
                                                {{ ucfirst($schedule->status) }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $statusClass }}">
                                            {{ str_replace('_', ' ', ucfirst($participant->status)) }}
                                        </span>
                                    </td>

                                    <td class="text-nowrap text-muted">
                                        {{ $participant->registered_at ? $participant->registered_at->format('d M Y H:i') : '-' }}
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-sm btn-outline-secondary dropdown-toggle px-3"
                                                type="button"
                                                data-bs-toggle="dropdown"
                                                data-bs-boundary="viewport"
                                                aria-expanded="false"
                                            >
                                                Actions
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item"
                                                        onclick="openEditModal(@js($editPayload))"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit Participant
                                                    </button>
                                                </li>

                                                

                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-danger"
                                                        onclick="openDeleteModal({{ $participant->id }}, @js($studentName))"
                                                    >
                                                        <i class="bi bi-trash me-2"></i>Delete
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($participants->hasPages())
                    <div class="mt-3">
                        {{ $participants->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>

                    <h5 class="empty-state-title">No workshop participants found</h5>
                    <p class="empty-state-text mb-0">
                        Tambahkan peserta workshop pertama. Sistem akan otomatis membuat order dan payment schedule.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Participant Form Modal --}}
<div class="modal fade" id="participantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="participantForm">
            @csrf
            <input type="hidden" id="participant_id">

            <div class="modal-content custom-modal">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="participantModalTitle">Add Workshop Participant</h5>
                        <p class="text-muted mb-0">
                            Input peserta workshop. Order dan payment schedule akan dibuat otomatis.
                        </p>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div id="formAlert" class="alert alert-danger d-none"></div>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="workshop_id" class="form-label">Workshop <span class="text-danger">*</span></label>
                            <select id="workshop_id" class="form-select">
                                <option value="">Select Workshop</option>
                                @foreach ($workshops as $workshop)
                                    <option value="{{ $workshop->id }}">
                                        {{ $workshop->title ?? $workshop->name ?? 'Workshop #' . $workshop->id }}
                                        @if(isset($workshop->price))
                                            - Rp {{ number_format($workshop->price, 0, ',', '.') }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="error_workshop_id"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="full_name" class="form-control" placeholder="Nama peserta">
                            <div class="invalid-feedback" id="error_full_name"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone / WhatsApp <span class="text-danger">*</span></label>
                            <input type="text" id="phone" class="form-control" placeholder="Nomor WhatsApp aktif">
                            <div class="invalid-feedback" id="error_phone"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" class="form-control" placeholder="Email peserta">
                            <div class="invalid-feedback" id="error_email"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="city" class="form-label">City</label>
                            <input type="text" id="city" class="form-control" placeholder="Kota / domisili">
                            <div class="invalid-feedback" id="error_city"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="discount" class="form-label">Discount</label>
                            <input type="number" id="discount" class="form-control" min="0" step="1000" value="0">
                            <div class="invalid-feedback" id="error_discount"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="due_date" class="form-label">Payment Due Date</label>
                            <input type="date" id="due_date" class="form-control" value="{{ now()->toDateString() }}">
                            <div class="invalid-feedback" id="error_due_date"></div>
                        </div>

                        <div class="col-md-6 edit-only d-none">
                            <label for="status" class="form-label">Participant Status</label>
                            <select id="status" class="form-select">
                                <option value="registered">Registered</option>
                                <option value="pending_payment">Pending Payment</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="attended">Attended</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <div class="invalid-feedback" id="error_status"></div>
                        </div>

                        <div class="col-md-12">
                            <label for="goal" class="form-label">Goal / Interest</label>
                            <textarea id="goal" rows="3" class="form-control" placeholder="Tujuan peserta mengikuti workshop"></textarea>
                            <div class="invalid-feedback" id="error_goal"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="notes" class="form-label">Participant Notes</label>
                            <textarea id="notes" rows="3" class="form-control" placeholder="Catatan peserta"></textarea>
                            <div class="invalid-feedback" id="error_notes"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="payment_notes" class="form-label">Payment Notes</label>
                            <textarea id="payment_notes" rows="3" class="form-control" placeholder="Catatan pembayaran"></textarea>
                            <div class="invalid-feedback" id="error_payment_notes"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary btn-modern" id="submitParticipantBtn">
                        <span class="default-text">
                            <i class="bi bi-save me-2"></i>Save Participant
                        </span>
                        <span class="loading-text d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteParticipantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title">Delete Participant</h5>
                    <p class="text-muted mb-0">
                        Konfirmasi hapus peserta workshop.
                    </p>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="delete-warning-box">
                    <div class="delete-warning-icon">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>

                    <div>
                        <h6 class="mb-1">Are you sure?</h6>
                        <p class="mb-0 text-muted">
                            Peserta <strong id="deleteParticipantName">-</strong> akan dihapus.
                            Jika sudah ada pembayaran paid, data akan dicancel, bukan dihapus permanen.
                        </p>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button" class="btn btn-danger" id="confirmDeleteParticipantBtn">
                    <span class="default-text">
                        <i class="bi bi-trash me-2"></i>Delete
                    </span>
                    <span class="loading-text d-none">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Deleting...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .dropdown-safe-table {
        overflow-x: auto;
        overflow-y: visible;
        padding-bottom: 96px;
        margin-bottom: -96px;
    }

    .admin-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 1px solid #e2e8f0;
    }

    .admin-table tbody td {
        vertical-align: middle;
        border-bottom: 1px solid #eef2f7;
    }

    .delete-warning-box {
        display: flex;
        gap: 1rem;
        padding: 1rem;
        border-radius: 1rem;
        background: #fff7ed;
        border: 1px solid #fed7aa;
    }

    .delete-warning-icon {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.9rem;
        background: #ffedd5;
        color: #ea580c;
        flex-shrink: 0;
        font-size: 1.2rem;
    }
</style>
@endpush

@push('scripts')
<script>
    let participantModal;
    let deleteParticipantModal;
    let deleteParticipantId = null;
    let isEditMode = false;

    const routes = {
        store: @js(route('academic.workshop-participants.store')),
        update: @js(route('academic.workshop-participants.update', ['workshopParticipant' => '__ID__'])),
        destroy: @js(route('academic.workshop-participants.destroy', ['workshopParticipant' => '__ID__'])),
    };

    const csrfToken = @js(csrf_token());

    const fields = {
        participant_id: document.getElementById('participant_id'),
        workshop_id: document.getElementById('workshop_id'),
        full_name: document.getElementById('full_name'),
        email: document.getElementById('email'),
        phone: document.getElementById('phone'),
        city: document.getElementById('city'),
        goal: document.getElementById('goal'),
        discount: document.getElementById('discount'),
        due_date: document.getElementById('due_date'),
        status: document.getElementById('status'),
        notes: document.getElementById('notes'),
        payment_notes: document.getElementById('payment_notes'),
    };

    document.addEventListener('DOMContentLoaded', function () {
        participantModal = new bootstrap.Modal(document.getElementById('participantModal'));
        deleteParticipantModal = new bootstrap.Modal(document.getElementById('deleteParticipantModal'));

        document.getElementById('participantForm').addEventListener('submit', submitParticipantForm);
        document.getElementById('confirmDeleteParticipantBtn').addEventListener('click', deleteParticipant);
    });

    function openCreateModal() {
        isEditMode = false;

        resetForm();

        document.getElementById('participantModalTitle').innerText = 'Add Workshop Participant';
        document.querySelectorAll('.edit-only').forEach(el => el.classList.add('d-none'));

        participantModal.show();
    }

    function openEditModal(payload) {
        isEditMode = true;

        resetForm();

        document.getElementById('participantModalTitle').innerText = 'Edit Workshop Participant';
        document.querySelectorAll('.edit-only').forEach(el => el.classList.remove('d-none'));

        fields.participant_id.value = payload.id || '';
        fields.workshop_id.value = payload.workshop_id || '';
        fields.full_name.value = payload.full_name || '';
        fields.email.value = payload.email || '';
        fields.phone.value = payload.phone || '';
        fields.city.value = payload.city || '';
        fields.goal.value = payload.goal || '';
        fields.discount.value = payload.discount ?? 0;
        fields.due_date.value = payload.due_date || @js(now()->toDateString());
        fields.status.value = payload.status || 'pending_payment';
        fields.notes.value = payload.notes || '';
        fields.payment_notes.value = payload.payment_notes || '';

        participantModal.show();
    }

    function openDeleteModal(id, name) {
        deleteParticipantId = id;
        document.getElementById('deleteParticipantName').innerText = name || '-';
        resetDeleteButton();
        deleteParticipantModal.show();
    }

    async function submitParticipantForm(event) {
        event.preventDefault();

        clearErrors();

        const submitButton = document.getElementById('submitParticipantBtn');
        setButtonLoading(submitButton, true);

        const payload = {
            workshop_id: fields.workshop_id.value,
            full_name: fields.full_name.value.trim(),
            email: fields.email.value.trim(),
            phone: fields.phone.value.trim(),
            city: fields.city.value.trim(),
            goal: fields.goal.value.trim(),
            discount: fields.discount.value || 0,
            due_date: fields.due_date.value,
            notes: fields.notes.value.trim(),
            payment_notes: fields.payment_notes.value.trim(),
        };

        if (isEditMode) {
            payload.status = fields.status.value;
        }

        const participantId = fields.participant_id.value;
        const url = isEditMode
            ? routes.update.replace('__ID__', participantId)
            : routes.store;

        const method = isEditMode ? 'PUT' : 'POST';

        try {
            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json();

            if (response.status === 422) {
                setValidationErrors(result.errors || {});
                throw new Error(result.message || 'Validation failed.');
            }

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to save participant.');
            }

            participantModal.hide();

            showToast(result.message || 'Participant saved successfully.', 'success');

            setTimeout(() => {
                window.location.reload();
            }, 700);
        } catch (error) {
            if (error.message !== 'Validation failed.') {
                showFormAlert(error.message || 'Something went wrong.');
                showToast(error.message || 'Something went wrong.', 'danger');
            }
        } finally {
            setButtonLoading(submitButton, false);
        }
    }

    async function deleteParticipant() {
        if (!deleteParticipantId) {
            return;
        }

        const deleteButton = document.getElementById('confirmDeleteParticipantBtn');
        setButtonLoading(deleteButton, true);

        try {
            const response = await fetch(routes.destroy.replace('__ID__', deleteParticipantId), {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete participant.');
            }

            deleteParticipantModal.hide();

            showToast(result.message || 'Participant deleted successfully.', 'success');

            setTimeout(() => {
                window.location.reload();
            }, 700);
        } catch (error) {
            showToast(error.message || 'Something went wrong.', 'danger');
        } finally {
            setButtonLoading(deleteButton, false);
        }
    }

    function resetForm() {
        document.getElementById('participantForm').reset();
        fields.participant_id.value = '';
        fields.discount.value = 0;
        fields.due_date.value = @js(now()->toDateString());
        fields.status.value = 'pending_payment';

        clearErrors();

        document.getElementById('formAlert').classList.add('d-none');
        document.getElementById('formAlert').innerHTML = '';
    }

    function clearErrors() {
        Object.values(fields).forEach(field => {
            if (!field || !field.classList) return;
            field.classList.remove('is-invalid');
        });

        document.querySelectorAll('[id^="error_"]').forEach(el => {
            el.innerText = '';
        });
    }

    function setValidationErrors(errors) {
        Object.keys(errors).forEach(key => {
            const field = fields[key];
            const errorEl = document.getElementById(`error_${key}`);

            if (field) {
                field.classList.add('is-invalid');
            }

            if (errorEl) {
                errorEl.innerText = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
            }
        });
    }

    function showFormAlert(message) {
        const alert = document.getElementById('formAlert');

        alert.innerHTML = message;
        alert.classList.remove('d-none');
    }

    function setButtonLoading(button, isLoading) {
        if (!button) return;

        button.disabled = isLoading;

        const defaultText = button.querySelector('.default-text');
        const loadingText = button.querySelector('.loading-text');

        if (defaultText) {
            defaultText.classList.toggle('d-none', isLoading);
        }

        if (loadingText) {
            loadingText.classList.toggle('d-none', !isLoading);
        }
    }

    function resetDeleteButton() {
        const button = document.getElementById('confirmDeleteParticipantBtn');
        setButtonLoading(button, false);
    }

    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');

        const toastId = 'toast-' + Date.now();

        const bgClass = {
            success: 'text-bg-success',
            danger: 'text-bg-danger',
            warning: 'text-bg-warning',
            info: 'text-bg-info',
        }[type] || 'text-bg-success';

        const toastEl = document.createElement('div');
        toastEl.id = toastId;
        toastEl.className = `toast align-items-center ${bgClass} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');

        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body fw-semibold">
                    ${message}
                </div>
                <button
                    type="button"
                    class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast"
                    aria-label="Close"
                ></button>
            </div>
        `;

        container.appendChild(toastEl);

        const toast = new bootstrap.Toast(toastEl, {
            delay: 3500,
        });

        toast.show();

        toastEl.addEventListener('hidden.bs.toast', () => {
            toastEl.remove();
        });
    }
</script>
@endpush