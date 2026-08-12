@extends('layouts.app-dashboard')

@section('title', 'Students')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Enrollment Management</div>
                <h1 class="page-title mb-2">Students</h1>
                <p class="page-subtitle mb-0">
                    Manage confirmed students, enrollment access, and LMS login simulation.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light btn-modern" onclick="openCreateModal()">
                    <i class="bi bi-plus-lg me-2"></i>Add Student
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
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total</div>
                        <div class="stat-value">{{ $stats['total'] ?? $students->total() }}</div>
                    </div>
                </div>
                <div class="stat-description">Total confirmed student data.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Active</div>
                        <div class="stat-value">{{ $stats['active'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Student yang sudah aktif.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-dash-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Inactive</div>
                        <div class="stat-value">{{ $stats['inactive'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Student yang belum aktif / belum diproses.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Enrolled</div>
                        <div class="stat-value">{{ $stats['enrolled'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Enrollment aktif untuk LMS.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">LMS Account</div>
                        <div class="stat-value">{{ $stats['login_ready'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Student yang sudah punya akun login LMS.</div>
            </div>
        </div>
    </div>

    <div class="content-card students-table-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Student List</h5>
                <p class="content-card-subtitle mb-0">
                    Kelola data student yang sudah confirm, lalu enroll ke batch untuk simulasi akses LMS.
                </p>
            </div>

            <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                <label for="per_page" class="form-label mb-0 small text-muted">Show</label>
                <select
                    name="per_page"
                    id="per_page"
                    class="form-select form-select-sm"
                    style="width: auto;"
                    onchange="this.form.submit()"
                >
                    @foreach ([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>
                            {{ $size }}
                        </option>
                    @endforeach
                </select>
                <span class="small text-muted">entries</span>
            </form>
        </div>

        <div class="content-card-body">
            @if($students->count())
                <div class="student-table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table student-admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 80px;">No</th>
                                <th class="text-nowrap col-student">Student</th>
                                <th class="text-nowrap col-contact">Contact</th>
                                <th class="text-nowrap col-profile">Profile</th>
                                <th class="text-nowrap">NIK</th>
                                <th class="text-nowrap col-emergency">Emergency Contact</th>
                                <th class="text-nowrap col-enrollment">Enrollment</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-end text-nowrap" style="width: 160px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($students as $student)
                                @php
                                    $statusClass = match($student->status) {
                                        'active' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
                                        'inactive' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                        default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                    };

                                    $activeEnrollments = $student->enrollments
                                        ? $student->enrollments->where('status', 'active')->where('access_status', 'active')
                                        : collect();

                                    $studentName = $student->full_name ?? $student->name ?? '-';

                                    $studentNik = $student->nik ?: '-';

                                    $emergencyContactName = $student->emergency_contact_name ?: null;
                                    $emergencyContactPhone = $student->emergency_contact_phone ?: null;
                                    $emergencyContactRelation = $student->emergency_contact_relation ?: null;
                                @endphp

                                <tr>
                                    <td class="text-muted">
                                        {{ ($students->currentPage() - 1) * $students->perPage() + $loop->iteration }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $studentName }}
                                        </div>

                                        @if ($student->goal)
                                            <div
                                                class="small text-muted text-truncate"
                                                style="max-width: 260px;"
                                                title="{{ $student->goal }}"
                                            >
                                                {{ $student->goal }}
                                            </div>
                                        @endif

                                        <div class="mt-2">
                                            @if($student->user)
                                                <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                                                    <i class="bi bi-person-check me-1"></i>Login Ready
                                                </span>
                                            @else
                                                <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                                                    <i class="bi bi-person-x me-1"></i>No Login Account
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $student->email ?: '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $student->phone ?: '-' }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $student->city ?: '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $student->current_status ?: '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            Source: {{ $student->source ?: '-' }}
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        @if($studentNik !== '-')
                                            <div class="fw-semibold text-dark">
                                                {{ $studentNik }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($emergencyContactName || $emergencyContactPhone || $emergencyContactRelation)
                                            <div class="fw-semibold text-dark">
                                                {{ $emergencyContactName ?: '-' }}
                                            </div>

                                            <div class="small text-muted">
                                                {{ $emergencyContactPhone ?: '-' }}
                                            </div>

                                            @if($emergencyContactRelation)
                                                <div class="small text-muted">
                                                    Relation: {{ $emergencyContactRelation }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($activeEnrollments->count())
                                            <div class="d-flex flex-column gap-1">
                                                @foreach($activeEnrollments->take(2) as $enrollment)
                                                    <div>
                                                        <span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle">
                                                            {{ $enrollment->batch->program->name ?? 'Program' }}
                                                            -
                                                            {{ $enrollment->batch->name ?? 'Batch' }}
                                                        </span>
                                                    </div>
                                                @endforeach

                                                @if($activeEnrollments->count() > 2)
                                                    <small class="text-muted">
                                                        +{{ $activeEnrollments->count() - 2 }} more enrollment
                                                    </small>
                                                @endif
                                            </div>
                                        @else
                                            <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                                                Not Enrolled
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $statusClass }}">
                                            {{ ucfirst($student->status) }}
                                        </span>
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
                                                        onclick="openEnrollModal(
                                                            {{ $student->id }},
                                                            @js($studentName),
                                                            @js($student->email),
                                                            '{{ route('students.enroll', $student->id) }}'
                                                        )"
                                                    >
                                                        <i class="bi bi-mortarboard me-2"></i>Enroll Student
                                                    </button>
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item"
                                                        onclick="editStudent({{ $student->id }})"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit Student
                                                    </button>
                                                </li>

                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-danger"
                                                        onclick="openDeleteModal({{ $student->id }}, @js($studentName))"
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

                @if ($students->hasPages())
                    <div class="mt-3">
                        {{ $students->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-people"></i>
                    </div>

                    <h5 class="empty-state-title">No students found</h5>
                    <p class="empty-state-text mb-0">
                        Add confirmed student data first, then enroll them to a batch.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Student Form Modal --}}
<div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form id="studentForm">
            @csrf
            <input type="hidden" id="student_id">

            <div class="modal-content custom-modal">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="studentModalTitle">Add Student</h5>
                        <p class="text-muted mb-0">Manage basic student information.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label">
                                Full Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="full_name" class="form-control">
                            <div class="invalid-feedback" id="error_full_name"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">
                                Student Status <span class="text-danger">*</span>
                            </label>
                            <select id="status" class="form-select">
                                <option value="inactive">Inactive</option>
                                <option value="active">Active</option>
                            </select>
                            <div class="invalid-feedback" id="error_status"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" class="form-control">
                            <div class="invalid-feedback" id="error_email"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" id="phone" class="form-control">
                            <div class="invalid-feedback" id="error_phone"></div>
                        </div>

                        <div class="col-12">
                            <div class="border-top pt-3 mt-1">
                                <div class="fw-semibold text-dark mb-1">Identity & Emergency Contact</div>
                                <div class="small text-muted mb-3">
                                    Optional data untuk identitas student dan kontak darurat.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="nik" class="form-label">
                                NIK <span class="text-muted">(Optional)</span>
                            </label>
                            <input
                                type="text"
                                id="nik"
                                class="form-control"
                                maxlength="16"
                                inputmode="numeric"
                                pattern="[0-9]{16}"
                                placeholder="16 digit NIK"
                            >
                            <div class="form-text">Isi 16 digit angka jika tersedia.</div>
                            <div class="invalid-feedback" id="error_nik"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="emergency_contact_name" class="form-label">
                                Emergency Contact Name <span class="text-muted">(Optional)</span>
                            </label>
                            <input
                                type="text"
                                id="emergency_contact_name"
                                class="form-control"
                                maxlength="255"
                                placeholder="Nama kontak darurat"
                            >
                            <div class="invalid-feedback" id="error_emergency_contact_name"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="emergency_contact_phone" class="form-label">
                                Emergency Contact Phone <span class="text-muted">(Optional)</span>
                            </label>
                            <input
                                type="text"
                                id="emergency_contact_phone"
                                class="form-control"
                                maxlength="30"
                                placeholder="Nomor kontak darurat"
                            >
                            <div class="invalid-feedback" id="error_emergency_contact_phone"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="emergency_contact_relation" class="form-label">
                                Emergency Contact Relation <span class="text-muted">(Optional)</span>
                            </label>
                            <input
                                type="text"
                                id="emergency_contact_relation"
                                class="form-control"
                                maxlength="100"
                                placeholder="Contoh: Parent, Sibling, Guardian"
                            >
                            <div class="invalid-feedback" id="error_emergency_contact_relation"></div>
                        </div>

                        <div class="col-12">
                            <div class="border-top pt-3 mt-1">
                                <div class="fw-semibold text-dark mb-1">Profile Information</div>
                                <div class="small text-muted mb-3">
                                    Data tambahan untuk segmentasi dan kebutuhan enrollment.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="city" class="form-label">City</label>
                            <input type="text" id="city" class="form-control">
                            <div class="invalid-feedback" id="error_city"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="current_status" class="form-label">Current Status</label>
                            <input type="text" id="current_status" class="form-control" placeholder="e.g. Student, Employee, Freelancer">
                            <div class="invalid-feedback" id="error_current_status"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="source" class="form-label">Source</label>
                            <input type="text" id="source" class="form-control" placeholder="e.g. Instagram, Referral, Event">
                            <div class="invalid-feedback" id="error_source"></div>
                        </div>

                        <div class="col-12">
                            <label for="goal" class="form-label">Goal</label>
                            <textarea id="goal" rows="4" class="form-control" placeholder="Why does this student want to join the program?"></textarea>
                            <div class="invalid-feedback" id="error_goal"></div>
                        </div>

                        <div class="col-12" id="initialPaymentSection">
                            <div class="initial-payment-section mt-2">
                                <div class="section-heading-row">
                                    <div>
                                        <div class="section-kicker">First Transaction</div>
                                        <h6 class="section-title mb-1">Initial Program & Payment</h6>
                                        <p class="section-description mb-0">
                                            Pilih kelas pertama student dan atur rencana pembayarannya. Sistem akan menyiapkan sales order, payment schedule, invoice, dan payment link.
                                        </p>
                                    </div>
                                    <span class="badge rounded-pill payment-flow-badge">
                                        <i class="bi bi-lightning-charge-fill me-1"></i>Auto Generate
                                    </span>
                                </div>

                                <div class="row g-3 mt-1">
                                    <div class="col-12">
                                        <label for="initial_batch_id" class="form-label">
                                            Initial Program & Batch <span class="text-danger">*</span>
                                        </label>
                                        <select id="initial_batch_id" class="form-select">
                                            <option value="">Select program and batch</option>
                                            @foreach($batches ?? [] as $batch)
                                                <option
                                                    value="{{ $batch->id }}"
                                                    data-price="{{ (int) round((float) ($batch->price ?? 0)) }}"
                                                >
                                                    {{ $batch->program->name ?? 'Program' }} - {{ $batch->name }}
                                                    - Rp {{ number_format((float) ($batch->price ?? 0), 0, ',', '.') }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">Kelas pertama yang akan dibeli oleh student.</div>
                                        <div class="invalid-feedback" id="error_initial_batch_id"></div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="regular_price" class="form-label">Regular Price <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" id="regular_price" class="form-control" min="0" step="1000" value="0" readonly>
                                        </div>
                                        <div class="form-text" id="regularPriceDisplay">Rp 0</div>
                                        <div class="invalid-feedback" id="error_regular_price"></div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="discount_type" class="form-label">Discount</label>
                                        <select id="discount_type" class="form-select">
                                            <option value="none">No Discount</option>
                                            <option value="fixed">Fixed Amount</option>
                                            <option value="percentage">Percentage</option>
                                        </select>
                                        <div class="invalid-feedback" id="error_discount_type"></div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="discount_value" class="form-label">Discount Value</label>
                                        <div class="input-group">
                                            <span class="input-group-text" id="discountPrefix">Rp</span>
                                            <input type="number" id="discount_value" class="form-control" min="0" step="1" value="0" disabled>
                                            <span class="input-group-text d-none" id="discountSuffix">%</span>
                                        </div>
                                        <div class="invalid-feedback" id="error_discount_value"></div>
                                    </div>

                                    <div class="col-12">
                                        <div class="price-summary-card">
                                            <div>
                                                <span class="price-summary-label">Final Price</span>
                                                <div class="small text-muted">
                                                    Diskon: <span id="discountAmountDisplay">Rp 0</span>
                                                </div>
                                            </div>
                                            <strong class="price-summary-value" id="finalPriceDisplay">Rp0</strong>
                                            <input type="hidden" id="discount_amount" value="0">
                                            <input type="hidden" id="final_price" value="0">
                                        </div>
                                        <div class="invalid-feedback d-block" id="error_final_price"></div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label d-block mb-2">Payment Scheme <span class="text-danger">*</span></label>
                                        <div class="payment-scheme-grid">
                                            <label class="payment-option active" id="fullPaymentOption">
                                                <input type="radio" name="payment_scheme" value="full" checked>
                                                <span class="payment-option-icon"><i class="bi bi-credit-card-2-front"></i></span>
                                                <span><strong>Full Payment</strong><small>Satu invoice untuk seluruh pembayaran.</small></span>
                                            </label>
                                            <label class="payment-option" id="installmentOption">
                                                <input type="radio" name="payment_scheme" value="installment">
                                                <span class="payment-option-icon"><i class="bi bi-calendar2-week"></i></span>
                                                <span><strong>Installment</strong><small>Bagi pembayaran menjadi beberapa termin.</small></span>
                                            </label>
                                        </div>
                                        <div class="invalid-feedback d-block" id="error_payment_scheme"></div>
                                    </div>

                                    <div class="col-12 d-none" id="installmentCountWrap">
                                        <label for="installment_count" class="form-label">Number of Terms</label>
                                        <select id="installment_count" class="form-select" style="max-width: 240px;">
                                            @foreach(range(2, 12) as $termCount)
                                                <option value="{{ $termCount }}" {{ $termCount === 2 ? 'selected' : '' }}>{{ $termCount }} Terms</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <div class="terms-card">
                                            <div class="terms-header">
                                                <div>
                                                    <h6 class="mb-1" id="termsTitle">Payment Detail</h6>
                                                    <p class="small text-muted mb-0">Tentukan nominal dan tanggal pembayaran.</p>
                                                </div>
                                                <span class="terms-status" id="termsStatus">Total sesuai</span>
                                            </div>
                                            <div id="paymentTermsContainer"></div>
                                            <div class="terms-total-row">
                                                <span>Total Payment Terms</span>
                                                <strong id="termsTotalDisplay">Rp0</strong>
                                            </div>
                                            <div class="text-danger small mt-2 d-none" id="termsMismatchMessage">
                                                Total nominal seluruh termin harus sama dengan Final Price.
                                            </div>
                                            <div class="invalid-feedback d-block" id="error_payment_terms"></div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="invoice_expiry_days" class="form-label">Payment Link Validity</label>
                                        <div class="input-group">
                                            <input type="number" id="invoice_expiry_days" class="form-control" min="1" max="365" value="3">
                                            <span class="input-group-text">Days</span>
                                        </div>
                                        <div class="form-text">Masa berlaku link dihitung sejak invoice dibuat.</div>
                                        <div class="invalid-feedback" id="error_invoice_expiry_days"></div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="payment_notes" class="form-label">Payment Notes</label>
                                        <textarea id="payment_notes" class="form-control" rows="2" placeholder="Optional notes for this order"></textarea>
                                        <div class="invalid-feedback" id="error_payment_notes"></div>
                                    </div>

                                    <div class="col-12">
                                        <div class="generation-preview">
                                            <i class="bi bi-info-circle-fill"></i>
                                            <div>
                                                <strong>Data yang dibuat setelah disimpan</strong>
                                                <span id="generationPreviewText">1 student, 1 sales order, 1 payment schedule, dan 1 invoice/payment link.</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-modern" id="submitBtn">
                        <span class="default-text">Save</span>
                        <span class="loading-text d-none">Saving...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Enroll Modal --}}
<div class="modal fade" id="enrollModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="enrollForm">
            @csrf

            <div class="modal-content custom-modal">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title">Enroll Student</h5>
                        <p class="text-muted mb-0" id="enrollStudentSubtitle">Enroll student to selected batch.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div id="enrollAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="soft-panel mb-3">
                        <div class="soft-panel-title" id="enrollStudentName">-</div>
                        <div class="soft-panel-subtitle" id="enrollStudentEmail">-</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="enroll_batch_id" class="form-label">
                                Batch <span class="text-danger">*</span>
                            </label>
                            <select id="enroll_batch_id" class="form-select" required>
                                <option value="">Select Batch</option>
                                @foreach($batches ?? [] as $batch)
                                    <option value="{{ $batch->id }}">
                                        {{ $batch->program->name ?? 'Program' }} - {{ $batch->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="error_enroll_batch_id"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="enroll_status" class="form-label">Enrollment Status</label>
                            <select id="enroll_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="on_hold">On Hold</option>
                            </select>
                            <div class="invalid-feedback" id="error_enroll_status"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="enroll_access_status" class="form-label">Access Status</label>
                            <select id="enroll_access_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                                <option value="expired">Expired</option>
                            </select>
                            <div class="invalid-feedback" id="error_enroll_access_status"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="enroll_enrolled_at" class="form-label">Enrolled At</label>
                            <input type="datetime-local" id="enroll_enrolled_at" class="form-control">
                            <div class="invalid-feedback" id="error_enroll_enrolled_at"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="enroll_access_expires_at" class="form-label">Access Expires At</label>
                            <input type="datetime-local" id="enroll_access_expires_at" class="form-control">
                            <div class="invalid-feedback" id="error_enroll_access_expires_at"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="enroll_create_user_account" class="form-label">LMS Login Account</label>
                            <select id="enroll_create_user_account" class="form-select">
                                <option value="1">Create / Link Student User</option>
                                <option value="0">Enrollment Only</option>
                            </select>
                            <div class="form-text">
                                Untuk simulasi LMS, pilih Create / Link Student User.
                            </div>
                            <div class="invalid-feedback" id="error_enroll_create_user_account"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="enroll_password" class="form-label">Default Password</label>
                            <input type="text" id="enroll_password" class="form-control" value="password">
                            <div class="form-text">
                                Dipakai saat user student baru dibuat.
                            </div>
                            <div class="invalid-feedback" id="error_enroll_password"></div>
                        </div>

                        <div class="col-12">
                            <label for="enroll_notes" class="form-label">Notes</label>
                            <textarea id="enroll_notes" rows="3" class="form-control" placeholder="Catatan enrollment..."></textarea>
                            <div class="invalid-feedback" id="error_enroll_notes"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-success btn-modern" id="enrollSubmitBtn">
                        <span class="default-text">Enroll Student</span>
                        <span class="loading-text d-none">Enrolling...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal delete-confirm-modal">
            <div class="modal-header border-0 pb-0">
                <div class="delete-confirm-heading">
                    <div class="delete-confirm-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h5 class="modal-title">Delete Student</h5>
                        <p class="text-muted mb-0">Konfirmasi sebelum menghapus student.</p>
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="delete-confirm-message">
                    <div class="delete-confirm-label">Student yang akan dihapus</div>
                    <div class="delete-confirm-name" id="deleteStudentName">-</div>
                </div>

                <div class="delete-confirm-warning mt-3">
                    Student yang sudah dihapus tidak bisa dikembalikan.
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button" class="btn btn-danger btn-modern" id="confirmDeleteBtn">
                    <span class="default-text">
                        <i class="bi bi-trash me-2"></i>Delete
                    </span>
                    <span class="loading-text d-none">Deleting...</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection


@push('styles')
<style>
    .students-table-card,
    .students-table-card .content-card-body {
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
    }

    .student-table-responsive {
        display: block;
        width: 100%;
        max-width: 100%;
        overflow-x: auto !important;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-x: contain;
    }

    .student-admin-table {
        width: 100%;
        min-width: 1180px;
        table-layout: auto;
    }

    .student-admin-table th,
    .student-admin-table td {
        vertical-align: top;
    }

    .student-admin-table .col-student {
        min-width: 220px;
    }

    .student-admin-table .col-contact {
        min-width: 220px;
    }

    .student-admin-table .col-profile {
        min-width: 190px;
    }

    .student-admin-table .col-emergency {
        min-width: 220px;
    }

    .student-admin-table .col-enrollment {
        min-width: 220px;
    }

    .student-admin-table .dropdown-menu {
        z-index: 1080;
    }

    #studentModal .modal-dialog {
        height: calc(100vh - 2rem);
        max-height: calc(100vh - 2rem);
        margin-top: 1rem;
        margin-bottom: 1rem;
    }

    #studentModal #studentForm {
        display: flex;
        width: 100%;
        max-height: 100%;
    }

    #studentModal .modal-content {
        display: flex;
        width: 100%;
        max-height: 100%;
        overflow: hidden;
    }

    #studentModal .modal-header,
    #studentModal .modal-footer {
        flex: 0 0 auto;
        background: #fff;
    }

    #studentModal .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
        -webkit-overflow-scrolling: touch;
    }

    .initial-payment-section { padding: 1.25rem; border: 1px solid #e9e2f4; border-radius: 16px; background: linear-gradient(180deg, #fcfaff 0%, #fff 100%); }
    .section-heading-row, .terms-header, .price-summary-card, .terms-total-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    .section-kicker { margin-bottom: .25rem; color: #5B3E8E; font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .section-title { color: #2d2438; font-weight: 700; }
    .section-description { color: #6c757d; font-size: .875rem; max-width: 760px; }
    .payment-flow-badge { color: #5B3E8E; background: #eee7f8; padding: .55rem .75rem; }
    .price-summary-card { padding: 1rem 1.1rem; border: 1px solid #e5dcf1; border-radius: 12px; background: #fff; }
    .price-summary-label { color: #4f455a; font-size: .85rem; font-weight: 600; }
    .price-summary-value { color: #5B3E8E; font-size: 1.45rem; }
    .payment-scheme-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .75rem; }
    .payment-option { display: flex; align-items: center; gap: .8rem; padding: 1rem; border: 1px solid #dee2e6; border-radius: 12px; background: #fff; cursor: pointer; transition: .18s ease; }
    .payment-option:hover { border-color: #a992c9; }
    .payment-option.active { border-color: #5B3E8E; box-shadow: 0 0 0 3px rgba(91, 62, 142, .1); }
    .payment-option input { margin: 0; accent-color: #5B3E8E; }
    .payment-option-icon { display: grid; width: 38px; height: 38px; flex: 0 0 38px; place-items: center; border-radius: 10px; color: #5B3E8E; background: #eee7f8; }
    .payment-option strong, .payment-option small { display: block; }
    .payment-option small { margin-top: .15rem; color: #6c757d; }
    .terms-card { padding: 1rem; border: 1px solid #e5e7eb; border-radius: 14px; background: #f9fafb; }
    .terms-status { padding: .3rem .6rem; border-radius: 999px; color: #20754a; background: #dcf5e7; font-size: .75rem; font-weight: 700; }
    .terms-status.mismatch { color: #a33a3a; background: #fde2e2; }
    .payment-term-row { display: grid; grid-template-columns: 90px minmax(180px, 1fr) minmax(170px, 1fr); gap: .75rem; align-items: end; padding: .85rem 0; border-bottom: 1px solid #e5e7eb; }
    .term-number { align-self: center; color: #5B3E8E; font-weight: 700; }
    .terms-total-row { padding-top: 1rem; }
    .generation-preview { display: flex; gap: .75rem; padding: .9rem 1rem; border-radius: 12px; color: #49336f; background: #eee7f8; }
    .generation-preview span { display: block; margin-top: .15rem; font-size: .85rem; }

    @media (max-width: 768px) {
        #studentModal .modal-dialog {
            height: calc(100vh - 1rem);
            max-height: calc(100vh - 1rem);
            margin: .5rem;
        }

        .container-fluid.px-4 {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        .content-card-header {
            align-items: flex-start;
            gap: 1rem;
        }

        .student-admin-table {
            min-width: 1080px;
        }

        .payment-scheme-grid, .payment-term-row { grid-template-columns: 1fr; }
        .term-number { padding-top: .25rem; }
        .section-heading-row { align-items: flex-start; flex-direction: column; }
    }
</style>
@endpush

@push('scripts')
<script>
let studentModal;
let deleteModal;
let enrollModal;
let deleteStudentId = null;
let enrollActionUrl = null;

document.addEventListener('DOMContentLoaded', function () {
    studentModal = new bootstrap.Modal(document.getElementById('studentModal'));
    deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    enrollModal = new bootstrap.Modal(document.getElementById('enrollModal'));

    document.getElementById('studentForm').addEventListener('submit', submitStudentForm);
    document.getElementById('confirmDeleteBtn').addEventListener('click', deleteStudent);
    document.getElementById('enrollForm').addEventListener('submit', submitEnrollForm);
    document.getElementById('initial_batch_id').addEventListener('change', handleInitialBatchChange);
    document.getElementById('discount_type').addEventListener('change', handleDiscountTypeChange);
    document.getElementById('discount_value').addEventListener('input', syncPaymentPlan);
    document.getElementById('installment_count').addEventListener('change', renderPaymentTerms);
    document.querySelectorAll('input[name="payment_scheme"]').forEach(input => input.addEventListener('change', handlePaymentSchemeChange));
});

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value) || 0);
}

function calculateFinalPrice() {
    const regularPrice = Math.max(0, Number(document.getElementById('regular_price').value) || 0);
    const type = document.getElementById('discount_type').value;
    let discount = Math.max(0, Number(document.getElementById('discount_value').value) || 0);
    if (type === 'percentage') discount = regularPrice * Math.min(discount, 100) / 100;
    if (type === 'none') discount = 0;
    return Math.max(0, Math.round(regularPrice - discount));
}

function calculateDiscountAmount() {
    const regularPrice = Math.max(0, Number(document.getElementById('regular_price').value) || 0);
    const type = document.getElementById('discount_type').value;
    const input = Math.max(0, Number(document.getElementById('discount_value').value) || 0);

    if (type === 'percentage') {
        return Math.min(regularPrice, Math.round(regularPrice * Math.min(input, 100) / 100));
    }

    if (type === 'fixed') {
        return Math.min(regularPrice, Math.round(input));
    }

    return 0;
}

function handleInitialBatchChange() {
    const select = document.getElementById('initial_batch_id');
    const selectedOption = select.options[select.selectedIndex];
    const regularPrice = select.value && selectedOption
        ? Math.max(0, Number(selectedOption.dataset.price) || 0)
        : 0;

    document.getElementById('regular_price').value = Math.round(regularPrice);
    document.getElementById('regularPriceDisplay').innerText = formatRupiah(regularPrice);
    syncPaymentPlan();
}

function handleDiscountTypeChange() {
    const type = document.getElementById('discount_type').value;
    const input = document.getElementById('discount_value');
    input.disabled = type === 'none';
    input.value = type === 'none' ? 0 : input.value;
    input.max = type === 'percentage' ? 100 : '';
    document.getElementById('discountPrefix').classList.toggle('d-none', type === 'percentage');
    document.getElementById('discountSuffix').classList.toggle('d-none', type !== 'percentage');
    syncPaymentPlan();
}

function handlePaymentSchemeChange() {
    const scheme = document.querySelector('input[name="payment_scheme"]:checked').value;
    document.getElementById('installmentCountWrap').classList.toggle('d-none', scheme !== 'installment');
    document.getElementById('fullPaymentOption').classList.toggle('active', scheme === 'full');
    document.getElementById('installmentOption').classList.toggle('active', scheme === 'installment');
    document.getElementById('termsTitle').innerText = scheme === 'full' ? 'Payment Detail' : 'Installment Schedule';
    document.getElementById('generationPreviewText').innerText = scheme === 'full'
        ? '1 student, 1 sales order, 1 payment schedule, dan 1 invoice/payment link.'
        : `${document.getElementById('installment_count').value} payment schedules dan invoice/payment link akan dibuat untuk student ini.`;
    renderPaymentTerms();
}

function syncPaymentPlan() {
    const discountAmount = calculateDiscountAmount();
    const finalPrice = calculateFinalPrice();
    document.getElementById('discount_amount').value = discountAmount;
    document.getElementById('discountAmountDisplay').innerText = formatRupiah(discountAmount);
    document.getElementById('final_price').value = finalPrice;
    document.getElementById('finalPriceDisplay').innerText = formatRupiah(finalPrice);
    renderPaymentTerms();
}

function renderPaymentTerms() {
    const container = document.getElementById('paymentTermsContainer');
    const scheme = document.querySelector('input[name="payment_scheme"]:checked')?.value || 'full';
    const count = scheme === 'installment' ? Number(document.getElementById('installment_count').value) : 1;
    const finalPrice = calculateFinalPrice();
    const existingDates = [...container.querySelectorAll('.term-due-date')].map(input => input.value);
    const baseAmount = Math.floor(finalPrice / count);
    const remainder = finalPrice - (baseAmount * count);
    container.innerHTML = '';

    for (let index = 0; index < count; index++) {
        const amount = baseAmount + (index === count - 1 ? remainder : 0);
        const row = document.createElement('div');
        row.className = 'payment-term-row';
        row.innerHTML = `
            <div class="term-number">${scheme === 'full' ? 'Payment' : `Term ${index + 1}`}</div>
            <div>
                <label class="form-label small">Amount <span class="text-danger">*</span></label>
                <div class="input-group"><span class="input-group-text">Rp</span><input type="number" class="form-control term-amount" min="0" step="1000" value="${amount}" ${scheme === 'full' || index === count - 1 ? 'readonly' : ''}></div>
            </div>
            <div>
                <label class="form-label small">Due Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control term-due-date" value="${existingDates[index] || ''}">
            </div>`;
        container.appendChild(row);
    }
    container.querySelectorAll('.term-amount').forEach((input, index) => {
        input.addEventListener('input', () => redistributeRemainingTerms(index));
    });
    updateTermsTotal();
}

function redistributeRemainingTerms(changedIndex) {
    const inputs = [...document.querySelectorAll('.term-amount')];
    const finalPrice = calculateFinalPrice();

    if (inputs.length <= 1) {
        if (inputs[0]) inputs[0].value = finalPrice;
        updateTermsTotal();
        return;
    }

    const previousTotal = inputs
        .slice(0, changedIndex)
        .reduce((sum, input) => sum + Math.max(0, Number(input.value) || 0), 0);
    const maximumCurrentAmount = Math.max(0, finalPrice - previousTotal);
    const currentAmount = Math.min(
        maximumCurrentAmount,
        Math.max(0, Number(inputs[changedIndex]?.value) || 0)
    );

    inputs[changedIndex].value = currentAmount;

    const fixedTotal = previousTotal + currentAmount;
    const remainingInputs = inputs.slice(changedIndex + 1);
    const remainingAmount = Math.max(0, finalPrice - fixedTotal);

    if (remainingInputs.length > 0) {
        const baseAmount = Math.floor(remainingAmount / remainingInputs.length);
        const remainder = remainingAmount - (baseAmount * remainingInputs.length);

        remainingInputs.forEach((input, index) => {
            input.value = baseAmount + (index === remainingInputs.length - 1 ? remainder : 0);
        });
    }

    updateTermsTotal();
}

function updateTermsTotal() {
    const total = [...document.querySelectorAll('.term-amount')].reduce((sum, input) => sum + (Number(input.value) || 0), 0);
    const matches = total === calculateFinalPrice();
    document.getElementById('termsTotalDisplay').innerText = formatRupiah(total);
    document.getElementById('termsMismatchMessage').classList.toggle('d-none', matches);
    document.getElementById('termsStatus').innerText = matches ? 'Total sesuai' : 'Total belum sesuai';
    document.getElementById('termsStatus').classList.toggle('mismatch', !matches);
}

function resetInitialPaymentForm() {
    document.getElementById('initial_batch_id').value = '';
    document.getElementById('regular_price').value = 0;
    document.getElementById('regularPriceDisplay').innerText = formatRupiah(0);
    document.getElementById('discount_type').value = 'none';
    document.getElementById('discount_value').value = 0;
    document.getElementById('invoice_expiry_days').value = 3;
    document.getElementById('payment_notes').value = '';
    document.getElementById('installment_count').value = 2;
    document.querySelector('input[name="payment_scheme"][value="full"]').checked = true;
    handleDiscountTypeChange();
    handlePaymentSchemeChange();
}

function csrfToken() {
    return document.querySelector('#studentForm input[name="_token"]')?.value || '{{ csrf_token() }}';
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');

    const toastId = 'toast-' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';

    container.insertAdjacentHTML('beforeend', `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);

    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl, { delay: 2500 });

    toast.show();

    toastEl.addEventListener('hidden.bs.toast', function () {
        toastEl.remove();
    });
}

function resetStudentErrors() {
    document.querySelectorAll('#studentForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#studentForm .invalid-feedback').forEach(el => el.innerText = '');
    document.getElementById('formAlert').classList.add('d-none');
    document.getElementById('formAlert').innerText = '';
}

function resetEnrollErrors() {
    document.querySelectorAll('#enrollForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#enrollForm .invalid-feedback').forEach(el => el.innerText = '');
    document.getElementById('enrollAlert').classList.add('d-none');
    document.getElementById('enrollAlert').innerText = '';
}

function setButtonLoading(button, loading = true) {
    const defaultText = button.querySelector('.default-text');
    const loadingText = button.querySelector('.loading-text');

    button.disabled = loading;

    if (defaultText) defaultText.classList.toggle('d-none', loading);
    if (loadingText) loadingText.classList.toggle('d-none', !loading);
}

function fillValidationErrors(prefix, errors, alertId) {
    const alert = document.getElementById(alertId);
    let firstMessage = 'Please check the form again.';

    Object.keys(errors || {}).forEach(function (field) {
        const messages = errors[field];
        const message = Array.isArray(messages) ? messages[0] : messages;

        firstMessage = message || firstMessage;

        const input = document.getElementById(prefix + field);
        const feedback = document.getElementById('error_' + prefix + field);

        if (input) input.classList.add('is-invalid');
        if (feedback) feedback.innerText = message;
    });

    alert.innerText = firstMessage;
    alert.classList.remove('d-none');
}

function openCreateModal() {
    resetStudentErrors();

    document.getElementById('studentModalTitle').innerText = 'Add Student';
    document.getElementById('student_id').value = '';
    document.getElementById('initialPaymentSection').classList.remove('d-none');

    document.getElementById('full_name').value = '';
    document.getElementById('status').value = 'inactive';
    document.getElementById('email').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('nik').value = '';
    document.getElementById('emergency_contact_name').value = '';
    document.getElementById('emergency_contact_phone').value = '';
    document.getElementById('emergency_contact_relation').value = '';
    document.getElementById('city').value = '';
    document.getElementById('current_status').value = '';
    document.getElementById('source').value = '';
    document.getElementById('goal').value = '';
    resetInitialPaymentForm();

    studentModal.show();
}

async function editStudent(id) {
    resetStudentErrors();

    try {
        const url = `{{ route('students.show', ['student' => '__ID__']) }}`.replace('__ID__', id);

        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const result = await response.json();

        if (!response.ok) {
            showToast(result.message || 'Failed to load student.', 'error');
            return;
        }

        const student = result.data || result;

        document.getElementById('studentModalTitle').innerText = 'Edit Student';
        document.getElementById('student_id').value = student.id;
        document.getElementById('initialPaymentSection').classList.add('d-none');

        document.getElementById('full_name').value = student.full_name || '';
        document.getElementById('status').value = student.status || 'inactive';
        document.getElementById('email').value = student.email || '';
        document.getElementById('phone').value = student.phone || '';
        document.getElementById('nik').value = student.nik || '';
        document.getElementById('emergency_contact_name').value = student.emergency_contact_name || '';
        document.getElementById('emergency_contact_phone').value = student.emergency_contact_phone || '';
        document.getElementById('emergency_contact_relation').value = student.emergency_contact_relation || '';
        document.getElementById('city').value = student.city || '';
        document.getElementById('current_status').value = student.current_status || '';
        document.getElementById('source').value = student.source || '';
        document.getElementById('goal').value = student.goal || '';

        studentModal.show();
    } catch (error) {
        showToast('Failed to load student.', 'error');
    }
}

async function submitStudentForm(event) {
    event.preventDefault();
    resetStudentErrors();

    const id = document.getElementById('student_id').value;

    if (!id) {
        const termRows = [...document.querySelectorAll('.payment-term-row')];
        const termsTotal = termRows.reduce((sum, row) => sum + (Number(row.querySelector('.term-amount').value) || 0), 0);
        const hasEmptyDueDate = termRows.some(row => !row.querySelector('.term-due-date').value);

        if (!document.getElementById('initial_batch_id').value) {
            document.getElementById('initial_batch_id').classList.add('is-invalid');
            document.getElementById('error_initial_batch_id').innerText = 'Please select the initial program and batch.';
            document.getElementById('initialPaymentSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }

        if (document.getElementById('regular_price').value === '') {
            document.getElementById('regular_price').classList.add('is-invalid');
            document.getElementById('error_regular_price').innerText = 'Regular price is required.';
            document.getElementById('initialPaymentSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }

        if (termsTotal !== calculateFinalPrice() || hasEmptyDueDate) {
            const message = hasEmptyDueDate
                ? 'Please complete the due date for every payment term.'
                : 'Total payment terms must be equal to the final price.';
            document.getElementById('error_payment_terms').innerText = message;
            document.getElementById('initialPaymentSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
    }

    const submitBtn = document.getElementById('submitBtn');
    setButtonLoading(submitBtn, true);

    const formData = new FormData();
    formData.append('_token', csrfToken());
    formData.append('full_name', document.getElementById('full_name').value);
    formData.append('status', document.getElementById('status').value);
    formData.append('email', document.getElementById('email').value);
    formData.append('phone', document.getElementById('phone').value);
    formData.append('nik', document.getElementById('nik').value);
    formData.append('emergency_contact_name', document.getElementById('emergency_contact_name').value);
    formData.append('emergency_contact_phone', document.getElementById('emergency_contact_phone').value);
    formData.append('emergency_contact_relation', document.getElementById('emergency_contact_relation').value);
    formData.append('city', document.getElementById('city').value);
    formData.append('current_status', document.getElementById('current_status').value);
    formData.append('source', document.getElementById('source').value);
    formData.append('goal', document.getElementById('goal').value);

    if (!id) {
        const paymentScheme = document.querySelector('input[name="payment_scheme"]:checked').value;
        formData.append('initial_batch_id', document.getElementById('initial_batch_id').value);
        formData.append('regular_price', document.getElementById('regular_price').value);
        formData.append('discount_type', document.getElementById('discount_type').value);
        formData.append('discount_value', document.getElementById('discount_value').value);
        formData.append('discount_amount', document.getElementById('discount_amount').value);
        formData.append('final_price', document.getElementById('final_price').value);
        formData.append('payment_scheme', paymentScheme);
        formData.append('invoice_expiry_days', document.getElementById('invoice_expiry_days').value);
        formData.append('payment_notes', document.getElementById('payment_notes').value);
        document.querySelectorAll('.payment-term-row').forEach((row, index) => {
            formData.append(`payment_terms[${index}][title]`, paymentScheme === 'full' ? 'Full Payment' : `Term ${index + 1}`);
            formData.append(`payment_terms[${index}][amount]`, row.querySelector('.term-amount').value);
            formData.append(`payment_terms[${index}][due_date]`, row.querySelector('.term-due-date').value);
        });
    }

    let url = '{{ route('students.store') }}';

    if (id) {
        url = `{{ route('students.update', ['student' => '__ID__']) }}`.replace('__ID__', id);
        formData.append('_method', 'PUT');
    }

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
            credentials: 'same-origin',
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok) {
            if (response.status === 422) {
                fillValidationErrors('', result.errors || {}, 'formAlert');
            } else {
                document.getElementById('formAlert').innerText = result.message || 'Failed to save student.';
                document.getElementById('formAlert').classList.remove('d-none');
            }

            setButtonLoading(submitBtn, false);
            return;
        }

        showToast(result.message || 'Student saved successfully.');
        studentModal.hide();

        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        document.getElementById('formAlert').innerText = 'Failed to connect to server.';
        document.getElementById('formAlert').classList.remove('d-none');
        setButtonLoading(submitBtn, false);
    }
}

function openEnrollModal(studentId, studentName, studentEmail, actionUrl) {
    resetEnrollErrors();

    enrollActionUrl = actionUrl;

    document.getElementById('enrollStudentName').innerText = studentName || '-';
    document.getElementById('enrollStudentEmail').innerText = studentEmail || 'No email set';
    document.getElementById('enrollStudentSubtitle').innerText = `Enroll ${studentName || 'student'} to selected batch.`;

    document.getElementById('enroll_batch_id').value = '';
    document.getElementById('enroll_status').value = 'active';
    document.getElementById('enroll_access_status').value = 'active';
    document.getElementById('enroll_enrolled_at').value = '';
    document.getElementById('enroll_access_expires_at').value = '';
    document.getElementById('enroll_create_user_account').value = '1';
    document.getElementById('enroll_password').value = 'password';
    document.getElementById('enroll_notes').value = '';

    enrollModal.show();
}

async function submitEnrollForm(event) {
    event.preventDefault();
    resetEnrollErrors();

    if (!enrollActionUrl) {
        showToast('Enroll route is missing.', 'error');
        return;
    }

    const submitBtn = document.getElementById('enrollSubmitBtn');
    setButtonLoading(submitBtn, true);

    const formData = new FormData();
    formData.append('_token', csrfToken());
    formData.append('batch_id', document.getElementById('enroll_batch_id').value);
    formData.append('status', document.getElementById('enroll_status').value);
    formData.append('access_status', document.getElementById('enroll_access_status').value);
    formData.append('enrolled_at', document.getElementById('enroll_enrolled_at').value);
    formData.append('access_expires_at', document.getElementById('enroll_access_expires_at').value);
    formData.append('create_user_account', document.getElementById('enroll_create_user_account').value);
    formData.append('password', document.getElementById('enroll_password').value);
    formData.append('notes', document.getElementById('enroll_notes').value);

    try {
        const response = await fetch(enrollActionUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
            credentials: 'same-origin',
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok) {
            if (response.status === 422) {
                fillValidationErrors('enroll_', result.errors || {}, 'enrollAlert');
            } else {
                document.getElementById('enrollAlert').innerText = result.message || 'Failed to enroll student.';
                document.getElementById('enrollAlert').classList.remove('d-none');
            }

            setButtonLoading(submitBtn, false);
            return;
        }

        showToast(result.message || 'Student enrolled successfully.');
        enrollModal.hide();

        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        document.getElementById('enrollAlert').innerText = 'Failed to connect to server.';
        document.getElementById('enrollAlert').classList.remove('d-none');
        setButtonLoading(submitBtn, false);
    }
}

function openDeleteModal(id, name) {
    deleteStudentId = id;
    document.getElementById('deleteStudentName').innerText = name;
    deleteModal.show();
}

async function deleteStudent() {
    if (!deleteStudentId) return;

    const deleteBtn = document.getElementById('confirmDeleteBtn');
    setButtonLoading(deleteBtn, true);

    const formData = new FormData();
    formData.append('_token', csrfToken());
    formData.append('_method', 'DELETE');

    try {
        const url = `{{ route('students.destroy', ['student' => '__ID__']) }}`.replace('__ID__', deleteStudentId);

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
            credentials: 'same-origin',
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok) {
            showToast(result.message || 'Failed to delete student.', 'error');
            setButtonLoading(deleteBtn, false);
            return;
        }

        showToast(result.message || 'Student deleted successfully.');
        deleteModal.hide();

        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        showToast('Failed to connect to server.', 'error');
        setButtonLoading(deleteBtn, false);
    }
}
</script>
@endpush