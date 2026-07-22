@extends('layouts.app-dashboard')

@section('title', 'Company Holidays')

@section('content')
@php
    $activeBadgeClass = fn (bool $isActive) => $isActive
        ? 'bg-success-subtle text-success-emphasis border border-success-subtle'
        : 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle';

    $holidayTypeBadgeClass = function (?string $type) {
        return match(strtolower((string) $type)) {
            'national', 'national_holiday' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
            'company', 'company_holiday' => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
            'collective_leave' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            default => 'bg-info-subtle text-info-emphasis border border-info-subtle',
        };
    };
@endphp

<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Human Resources</div>
                <h1 class="page-title mb-2">Company Holidays</h1>
                <p class="page-subtitle mb-0">
                    Kelola hari libur nasional, cuti bersama, dan libur internal agar tidak dianggap missing attendance.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light btn-modern" onclick="openHolidayCreateModal()">
                    <i class="bi bi-calendar-plus-fill me-2"></i>Add Holiday
                </button>
            </div>
        </div>
    </div>

    <div
        id="holidayToastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <div class="row g-3 mb-4">
        @foreach ([
            [
                'label' => 'Total Holidays',
                'value' => $summary['total'],
                'icon' => 'bi bi-calendar2-event-fill',
                'description' => 'Seluruh hari libur yang tersimpan pada master.',
            ],
            [
                'label' => 'Active',
                'value' => $summary['active'],
                'icon' => 'bi bi-calendar-check-fill',
                'description' => 'Hari libur aktif yang digunakan oleh attendance engine.',
            ],
            [
                'label' => 'This Year',
                'value' => $summary['this_year'],
                'icon' => 'bi bi-calendar3',
                'description' => 'Jumlah hari libur pada tahun berjalan.',
            ],
            [
                'label' => 'Upcoming',
                'value' => $summary['upcoming'],
                'icon' => 'bi bi-calendar2-week-fill',
                'description' => 'Hari libur aktif yang akan datang.',
            ],
        ] as $stat)
            <div class="col-xl col-md-6">
                <div class="stat-card h-100">
                    <div class="stat-card-top">
                        <div class="stat-icon-wrap">
                            <i class="{{ $stat['icon'] }}"></i>
                        </div>
                        <div>
                            <div class="stat-title">{{ $stat['label'] }}</div>
                            <div class="stat-value">{{ number_format($stat['value']) }}</div>
                        </div>
                    </div>
                    <div class="stat-description">{{ $stat['description'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="content-card holiday-table-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Holiday List</h5>
                <p class="content-card-subtitle mb-0">
                    Review tanggal libur, jenis, status, dan catatan internal.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('hr.company-holidays.index') }}"
                class="master-filter-form d-flex align-items-center gap-2 flex-wrap"
            >
                <input
                    type="date"
                    name="date_from"
                    class="form-control form-control-sm"
                    value="{{ $filters['date_from'] ?? '' }}"
                    style="width: 145px;"
                    onchange="this.form.submit()"
                    title="Date from"
                >

                <input
                    type="date"
                    name="date_to"
                    class="form-control form-control-sm"
                    value="{{ $filters['date_to'] ?? '' }}"
                    style="width: 145px;"
                    onchange="this.form.submit()"
                    title="Date to"
                >

                <select
                    name="holiday_type"
                    class="form-select form-select-sm"
                    style="width: 165px;"
                    onchange="this.form.submit()"
                >
                    <option value="">All Types</option>
                    @foreach ($holidayTypeOptions as $holidayType)
                        <option
                            value="{{ $holidayType }}"
                            {{ ($filters['holiday_type'] ?? '') === $holidayType ? 'selected' : '' }}
                        >
                            {{ ucwords(str_replace('_', ' ', $holidayType)) }}
                        </option>
                    @endforeach
                </select>

                <select
                    name="is_active"
                    class="form-select form-select-sm"
                    style="width: 135px;"
                    onchange="this.form.submit()"
                >
                    <option value="">All Status</option>
                    <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>

                <div class="input-group input-group-sm master-search-group">
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Search holiday..."
                    >
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>

                @if (collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
                    <a href="{{ route('hr.company-holidays.index') }}" class="btn btn-sm btn-outline-secondary">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <div class="content-card-body">
            @if ($companyHolidays->count())
                <div class="master-table-responsive">
                    <table class="table table-hover align-middle admin-table master-admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 65px;">No</th>
                                <th class="text-nowrap">Date</th>
                                <th class="text-nowrap col-holiday">Holiday</th>
                                <th class="text-nowrap">Type</th>
                                <th class="text-nowrap col-notes">Notes</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-end text-nowrap" style="width: 130px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($companyHolidays as $holiday)
                                @php
                                    $holidayPayload = [
                                        'id' => $holiday->id,
                                        'holiday_date' => $holiday->holiday_date?->format('Y-m-d'),
                                        'name' => $holiday->name,
                                        'holiday_type' => $holiday->holiday_type,
                                        'notes' => $holiday->notes,
                                        'is_active' => (bool) $holiday->is_active,
                                    ];

                                    $encodedHolidayPayload = base64_encode(json_encode(
                                        $holidayPayload,
                                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                    ));
                                @endphp

                                <tr>
                                    <td class="text-muted">
                                        {{ ($companyHolidays->currentPage() - 1) * $companyHolidays->perPage() + $loop->iteration }}
                                    </td>

                                    <td class="text-nowrap">
                                        <div class="fw-bold text-dark">
                                            {{ $holiday->holiday_date?->format('d M Y') ?? '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $holiday->holiday_date?->format('l') ?? '' }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-bold text-dark">{{ $holiday->name }}</div>

                                        @if ($holiday->holiday_date?->isToday())
                                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle mt-1">
                                                Today
                                            </span>
                                        @elseif ($holiday->holiday_date?->isFuture())
                                            <div class="small text-muted mt-1">
                                                {{ $holiday->holiday_date->diffForHumans() }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $holidayTypeBadgeClass($holiday->holiday_type) }}">
                                            {{ $holiday->holiday_type
                                                ? ucwords(str_replace('_', ' ', $holiday->holiday_type))
                                                : 'Other' }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="holiday-notes">
                                            {{ $holiday->notes ?: '-' }}
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $activeBadgeClass((bool) $holiday->is_active) }}">
                                            {{ $holiday->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-sm btn-outline-secondary dropdown-toggle px-3"
                                                type="button"
                                                data-bs-toggle="dropdown"
                                                data-bs-boundary="viewport"
                                            >
                                                Actions
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item holiday-edit-button"
                                                        data-payload="{{ $encodedHolidayPayload }}"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit Holiday
                                                    </button>
                                                </li>

                                                <li><hr class="dropdown-divider"></li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-danger holiday-delete-button"
                                                        data-id="{{ $holiday->id }}"
                                                        data-name="{{ $holiday->name }}"
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

                @if ($companyHolidays->hasPages())
                    <div class="mt-3">
                        {{ $companyHolidays->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-calendar2-event"></i>
                    </div>
                    <h5 class="empty-state-title">No company holidays found</h5>
                    <p class="empty-state-text mb-0">
                        Tambahkan hari libur agar tanggal tersebut tidak dibuat sebagai missing attendance.
                    </p>
                    <div class="mt-3">
                        <button type="button" class="btn btn-primary btn-modern" onclick="openHolidayCreateModal()">
                            <i class="bi bi-calendar-plus-fill me-2"></i>Add Holiday
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="holidayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="holidayForm">
            @csrf
            <input type="hidden" id="holiday_id">

            <div class="modal-content custom-modal">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="holidayModalTitle">Add Company Holiday</h5>
                        <p class="text-muted mb-0">
                            Tentukan tanggal libur, jenis, dan catatan untuk attendance engine.
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-4">
                    <div id="holidayFormAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Holiday Information</h5>
                                <p class="content-card-subtitle mb-0">
                                    Satu tanggal dapat memiliki lebih dari satu entry selama nama hari liburnya berbeda.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-5">
                                    <label for="holiday_date" class="form-label">
                                        Holiday Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" id="holiday_date" class="form-control">
                                    <div class="invalid-feedback" id="error_holiday_date"></div>
                                </div>

                                <div class="col-12 col-md-7">
                                    <label for="holiday_name" class="form-label">
                                        Holiday Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="holiday_name" class="form-control">
                                    <div class="invalid-feedback" id="error_name"></div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="holiday_type" class="form-label">Holiday Type</label>
                                    <input
                                        type="text"
                                        id="holiday_type"
                                        class="form-control"
                                        list="holidayTypeList"
                                        placeholder="e.g. national"
                                    >
                                    <datalist id="holidayTypeList">
                                        <option value="national"></option>
                                        <option value="company"></option>
                                        <option value="collective_leave"></option>
                                        @foreach ($holidayTypeOptions as $option)
                                            <option value="{{ $option }}"></option>
                                        @endforeach
                                    </datalist>
                                    <div class="invalid-feedback" id="error_holiday_type"></div>
                                </div>

                                <div class="col-12 col-md-6 d-flex align-items-end">
                                    <div>
                                        <div class="form-check form-switch mb-2">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                id="holiday_is_active"
                                                checked
                                            >
                                            <label class="form-check-label fw-semibold" for="holiday_is_active">
                                                Active Holiday
                                            </label>
                                        </div>
                                        <div class="form-text">
                                            Hanya holiday aktif yang digunakan oleh attendance engine.
                                        </div>
                                        <div class="invalid-feedback d-block" id="error_is_active"></div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="holiday_notes" class="form-label">Notes</label>
                                    <textarea
                                        id="holiday_notes"
                                        class="form-control"
                                        rows="4"
                                        placeholder="Catatan internal atau dasar penetapan libur..."
                                    ></textarea>
                                    <div class="invalid-feedback" id="error_notes"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary btn-modern" id="submitHolidayBtn">
                        <span class="default-text">
                            <i class="bi bi-save me-2"></i>Save Holiday
                        </span>
                        <span class="loading-text d-none">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="deleteHolidayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal delete-confirm-modal">
            <div class="modal-header border-0 pb-0">
                <div class="delete-confirm-heading">
                    <div class="delete-confirm-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>

                    <div>
                        <h5 class="modal-title">Delete Company Holiday</h5>
                        <p class="text-muted mb-0">Konfirmasi sebelum menghapus hari libur.</p>
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="delete-confirm-message">
                    <div class="delete-confirm-label">Holiday yang akan dihapus</div>
                    <div class="delete-confirm-name" id="deleteHolidayName">-</div>
                </div>

                <div class="delete-confirm-warning mt-3">
                    Setelah dihapus, tanggal ini tidak lagi dianggap sebagai company holiday pada proses attendance berikutnya.
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button" class="btn btn-danger btn-modern" id="confirmDeleteHolidayBtn">
                    <span class="default-text">
                        <i class="bi bi-trash me-2"></i>Delete
                    </span>
                    <span class="loading-text d-none">
                        <span class="spinner-border spinner-border-sm me-2"></span>
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
    .holiday-table-card,
    .holiday-table-card .content-card-body {
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
    }

    .master-filter-form {
        justify-content: flex-end;
    }

    .master-search-group {
        width: 235px;
    }

    .master-table-responsive {
        display: block;
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 90px;
        margin-bottom: -90px;
    }

    .master-admin-table {
        min-width: 980px;
    }

    .master-admin-table .col-holiday {
        min-width: 240px;
    }

    .master-admin-table .col-notes {
        min-width: 280px;
    }

    .admin-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: .78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
        border-bottom: 1px solid #e2e8f0;
    }

    .admin-table tbody td {
        border-bottom: 1px solid #eef2f7;
    }

    .holiday-notes {
        max-width: 360px;
        color: #64748b;
        white-space: normal;
    }

    .delete-confirm-heading {
        display: flex;
        align-items: center;
        gap: .9rem;
    }

    .delete-confirm-icon {
        width: 44px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 1rem;
        background: #fee2e2;
        color: #dc2626;
        font-size: 1.25rem;
    }

    .delete-confirm-message {
        border: 1px solid #fecaca;
        background: #fff1f2;
        border-radius: 1rem;
        padding: 1rem;
    }

    .delete-confirm-label {
        font-size: .78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #9f1239;
        margin-bottom: .25rem;
    }

    .delete-confirm-name {
        font-size: 1rem;
        font-weight: 800;
        color: #111827;
    }

    .delete-confirm-warning {
        border-radius: 1rem;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        color: #9a3412;
        padding: .9rem 1rem;
        font-weight: 700;
        font-size: .9rem;
    }

    @media (max-width: 768px) {
        .container-fluid.px-4 {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        .master-filter-form,
        .master-search-group {
            width: 100% !important;
        }

        .master-filter-form .form-select,
        .master-filter-form .form-control,
        .master-filter-form .btn {
            flex: 1 1 145px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    let holidayModal = null;
    let deleteHolidayModal = null;
    let holidayEditMode = false;
    let deleteHolidayId = null;

    const holidayRoutes = {
        store: @js(route('hr.company-holidays.store')),
        update: @js(route('hr.company-holidays.update', ['companyHoliday' => '__ID__'])),
        destroy: @js(route('hr.company-holidays.destroy', ['companyHoliday' => '__ID__'])),
    };

    const holidayCsrfToken = @js(csrf_token());
    const holidayFields = {};

    document.addEventListener('DOMContentLoaded', () => {
        holidayModal = bootstrap.Modal.getOrCreateInstance(
            document.getElementById('holidayModal')
        );

        deleteHolidayModal = bootstrap.Modal.getOrCreateInstance(
            document.getElementById('deleteHolidayModal')
        );

        Object.assign(holidayFields, {
            id: document.getElementById('holiday_id'),
            holiday_date: document.getElementById('holiday_date'),
            name: document.getElementById('holiday_name'),
            holiday_type: document.getElementById('holiday_type'),
            notes: document.getElementById('holiday_notes'),
            is_active: document.getElementById('holiday_is_active'),
        });

        document.getElementById('holidayForm')
            .addEventListener('submit', submitHolidayForm);

        document.getElementById('confirmDeleteHolidayBtn')
            .addEventListener('click', deleteHoliday);

        document.querySelectorAll('.holiday-edit-button').forEach(button => {
            button.addEventListener('click', () => {
                const payload = decodeHolidayPayload(button.dataset.payload);

                if (payload) {
                    openHolidayEditModal(payload);
                }
            });
        });

        document.querySelectorAll('.holiday-delete-button').forEach(button => {
            button.addEventListener('click', () => {
                openHolidayDeleteModal(
                    button.dataset.id,
                    button.dataset.name
                );
            });
        });
    });

    window.openHolidayCreateModal = function () {
        holidayEditMode = false;
        resetHolidayForm();

        document.getElementById('holidayModalTitle').textContent =
            'Add Company Holiday';

        holidayFields.holiday_type.value = 'national';
        holidayFields.is_active.checked = true;

        holidayModal.show();
    };

    function openHolidayEditModal(payload) {
        holidayEditMode = true;
        resetHolidayForm();

        document.getElementById('holidayModalTitle').textContent =
            'Edit Company Holiday';

        holidayFields.id.value = payload.id ?? '';
        holidayFields.holiday_date.value = payload.holiday_date ?? '';
        holidayFields.name.value = payload.name ?? '';
        holidayFields.holiday_type.value = payload.holiday_type ?? '';
        holidayFields.notes.value = payload.notes ?? '';
        holidayFields.is_active.checked = Boolean(payload.is_active);

        holidayModal.show();
    }

    function openHolidayDeleteModal(id, name) {
        deleteHolidayId = id;
        document.getElementById('deleteHolidayName').textContent = name || '-';

        setHolidayButtonLoading(
            document.getElementById('confirmDeleteHolidayBtn'),
            false
        );

        deleteHolidayModal.show();
    }

    async function submitHolidayForm(event) {
        event.preventDefault();
        clearHolidayErrors();

        const button = document.getElementById('submitHolidayBtn');
        setHolidayButtonLoading(button, true);

        const payload = {
            holiday_date: holidayFields.holiday_date.value,
            name: holidayFields.name.value.trim(),
            holiday_type: holidayFields.holiday_type.value.trim() || null,
            notes: holidayFields.notes.value.trim() || null,
            is_active: holidayFields.is_active.checked,
        };

        const holidayId = holidayFields.id.value;
        const url = holidayEditMode
            ? holidayRoutes.update.replace('__ID__', holidayId)
            : holidayRoutes.store;

        try {
            const response = await fetch(url, {
                method: holidayEditMode ? 'PUT' : 'POST',
                headers: holidayJsonHeaders(),
                body: JSON.stringify(payload),
            });

            const result = await parseHolidayResponse(response);

            if (response.status === 422) {
                setHolidayValidationErrors(result.errors || {});
                throw new Error('Validation failed.');
            }

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Holiday gagal disimpan.');
            }

            holidayModal.hide();
            showHolidayToast(
                result.message || 'Holiday berhasil disimpan.',
                'success'
            );

            setTimeout(() => window.location.reload(), 650);
        } catch (error) {
            if (error.message !== 'Validation failed.') {
                showHolidayFormAlert(error.message);
                showHolidayToast(error.message || 'Terjadi kesalahan.', 'danger');
            }
        } finally {
            setHolidayButtonLoading(button, false);
        }
    }

    async function deleteHoliday() {
        if (!deleteHolidayId) {
            return;
        }

        const button = document.getElementById('confirmDeleteHolidayBtn');
        setHolidayButtonLoading(button, true);

        try {
            const response = await fetch(
                holidayRoutes.destroy.replace('__ID__', deleteHolidayId),
                {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': holidayCsrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }
            );

            const result = await parseHolidayResponse(response);

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Holiday gagal dihapus.');
            }

            deleteHolidayModal.hide();
            showHolidayToast(
                result.message || 'Holiday berhasil dihapus.',
                'success'
            );

            setTimeout(() => window.location.reload(), 650);
        } catch (error) {
            showHolidayToast(
                error.message || 'Holiday gagal dihapus.',
                'danger'
            );
        } finally {
            setHolidayButtonLoading(button, false);
        }
    }

    function resetHolidayForm() {
        document.getElementById('holidayForm').reset();
        holidayFields.id.value = '';
        holidayFields.is_active.checked = true;

        clearHolidayErrors();
        hideHolidayFormAlert();
    }

    function clearHolidayErrors() {
        document.querySelectorAll('#holidayForm .is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
        });

        document.querySelectorAll('#holidayForm .invalid-feedback').forEach(error => {
            error.textContent = '';
        });
    }

    function setHolidayValidationErrors(errors) {
        Object.entries(errors).forEach(([key, messages]) => {
            const baseKey = key.split('.')[0];
            const field = holidayFields[baseKey];
            const errorElement = document.getElementById(`error_${baseKey}`);
            const message = Array.isArray(messages) ? messages[0] : messages;

            field?.classList.add('is-invalid');

            if (errorElement) {
                errorElement.textContent = message;
            }
        });
    }

    function showHolidayFormAlert(message) {
        const alert = document.getElementById('holidayFormAlert');
        alert.textContent = message || 'Terjadi kesalahan.';
        alert.classList.remove('d-none');
    }

    function hideHolidayFormAlert() {
        const alert = document.getElementById('holidayFormAlert');
        alert.textContent = '';
        alert.classList.add('d-none');
    }

    function decodeHolidayPayload(encodedPayload) {
        try {
            const binary = atob(encodedPayload);
            const bytes = Uint8Array.from(
                binary,
                character => character.charCodeAt(0)
            );

            return JSON.parse(new TextDecoder('utf-8').decode(bytes));
        } catch (error) {
            console.error('Holiday payload could not be decoded.', error);
            return null;
        }
    }

    function holidayJsonHeaders() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': holidayCsrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        };
    }

    async function parseHolidayResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            throw new Error('Server mengembalikan response yang tidak valid.');
        }

        return response.json();
    }

    function setHolidayButtonLoading(button, loading) {
        if (!button) {
            return;
        }

        button.disabled = loading;
        button.querySelector('.default-text')?.classList.toggle('d-none', loading);
        button.querySelector('.loading-text')?.classList.toggle('d-none', !loading);
    }

    function showHolidayToast(message, type = 'success') {
        const container = document.getElementById('holidayToastContainer');
        const toastElement = document.createElement('div');

        const backgroundClass = {
            success: 'text-bg-success',
            danger: 'text-bg-danger',
            warning: 'text-bg-warning',
            info: 'text-bg-info',
        }[type] || 'text-bg-success';

        toastElement.className = `toast align-items-center ${backgroundClass} border-0`;
        toastElement.setAttribute('role', 'alert');

        const wrapper = document.createElement('div');
        wrapper.className = 'd-flex';

        const body = document.createElement('div');
        body.className = 'toast-body fw-semibold';
        body.textContent = message;

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'btn-close btn-close-white me-2 m-auto';
        close.setAttribute('data-bs-dismiss', 'toast');

        wrapper.append(body, close);
        toastElement.appendChild(wrapper);
        container.appendChild(toastElement);

        const toast = new bootstrap.Toast(toastElement, { delay: 3200 });
        toast.show();

        toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
    }
</script>
@endpush
