@extends('layouts.app-dashboard')

@section('title', 'Equipment')

@section('content')
@php
    $typeBadgeClass = function ($type) {
        return match($type ?? 'borrowable') {
            'assigned' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
            default => 'bg-dark-subtle text-dark-emphasis border border-dark-subtle',
        };
    };

    $conditionBadgeClass = function ($condition) {
        return match($condition) {
            'good' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
            'minor_damage' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'damaged' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
            default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
        };
    };

    $statusBadgeClass = function ($status) {
        return match($status) {
            'available' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
            'borrowed' => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
            'maintenance' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
        };
    };

    $formatCondition = function ($condition) {
        return match($condition) {
            'good' => 'Good',
            'minor_damage' => 'Minor Damage',
            'damaged' => 'Damaged',
            default => ucfirst((string) $condition),
        };
    };

    $formatType = function ($type) {
        return ($type ?? 'borrowable') === 'assigned' ? 'Assigned' : 'Borrowable';
    };

    $formatStatus = function ($status) {
        return ucfirst((string) $status);
    };
@endphp

<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Operations</div>
                <h1 class="page-title mb-2">Equipment</h1>
                <p class="page-subtitle mb-0">
                    Manage equipment master data, asset assignment, borrowing activity, return condition, and equipment history in one place.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light btn-modern" onclick="openCreateModal()">
                    <i class="bi bi-plus-lg me-2"></i>Add Equipment
                </button>
            </div>
        </div>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Equipment List</h5>
                <p class="content-card-subtitle mb-0">
                    Review equipment code, type, condition, availability status, holder, and borrowing/assignment history.
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
            @if($equipments->count())
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 80px;">No</th>
                                <th class="text-nowrap">Equipment</th>
                                <th class="text-nowrap">Code</th>
                                <th class="text-nowrap">Type</th>
                                <th class="text-nowrap">Serial Number</th>
                                <th class="text-nowrap">Brand / Model</th>
                                <th class="text-nowrap">Condition</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-nowrap">Holder</th>
                                <th class="text-end text-nowrap" style="width: 160px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($equipments as $equipment)
                                @php
                                    $activeBorrowing = $equipment->activeBorrowing;
                                    $equipmentType = $equipment->type ?? 'borrowable';
                                @endphp

                                <tr>
                                    <td class="text-muted">
                                        {{ ($equipments->currentPage() - 1) * $equipments->perPage() + $loop->iteration }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">{{ $equipment->name }}</div>
                                        @if ($equipment->description)
                                            <div class="small text-muted">
                                                {{ \Illuminate\Support\Str::limit($equipment->description, 50) }}
                                            </div>
                                        @else
                                            <div class="small text-muted">No description</div>
                                        @endif
                                    </td>

                                    <td class="text-nowrap">
                                        <code>{{ $equipment->code }}</code>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $typeBadgeClass($equipmentType) }}">
                                            {{ $formatType($equipmentType) }}
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        {{ $equipment->serial_number ?: '-' }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">{{ $equipment->brand ?: '-' }}</div>
                                        <div class="small text-muted">{{ $equipment->model ?: '-' }}</div>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $conditionBadgeClass($equipment->condition) }}">
                                            {{ $formatCondition($equipment->condition) }}
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $statusBadgeClass($equipment->status) }}">
                                            {{ $formatStatus($equipment->status) }}
                                        </span>
                                    </td>

                                    <td>
                                        @if ($equipmentType === 'assigned')
                                            @if ($equipment->assignedUser)
                                                <div class="fw-semibold text-dark">{{ $equipment->assignedUser->name }}</div>
                                                <div class="small text-muted">Assigned User</div>
                                            @else
                                                <span class="text-muted">Not Assigned</span>
                                            @endif
                                        @else
                                            @if ($activeBorrowing && $activeBorrowing->user)
                                                <div class="fw-semibold text-dark">{{ $activeBorrowing->user->name }}</div>
                                                <div class="small text-muted">
                                                    {{ $activeBorrowing->borrowed_at?->format('d M Y H:i') }}
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        @endif
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
                                                        onclick="openHistoryModal({{ $equipment->id }})"
                                                    >
                                                        <i class="bi bi-clock-history me-2"></i>View History
                                                    </button>
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item"
                                                        onclick="editEquipment({{ $equipment->id }})"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit Equipment
                                                    </button>
                                                </li>

                                                @if ($equipmentType === 'borrowable' && $equipment->status === 'available')
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>

                                                    <li>
                                                        <button
                                                            type="button"
                                                            class="dropdown-item text-success"
                                                            onclick="openBorrowModal({{ $equipment->id }}, @js($equipment->name))"
                                                        >
                                                            <i class="bi bi-box-arrow-right me-2"></i>Borrow Equipment
                                                        </button>
                                                    </li>
                                                @endif

                                                @if ($equipmentType === 'borrowable' && $equipment->status === 'borrowed' && $activeBorrowing)
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>

                                                    <li>
                                                        <button
                                                            type="button"
                                                            class="dropdown-item text-warning"
                                                            onclick="openReturnModal({{ $activeBorrowing->id }}, @js($equipment->name), @js($equipment->condition))"
                                                        >
                                                            <i class="bi bi-box-arrow-in-left me-2"></i>Return Equipment
                                                        </button>
                                                    </li>
                                                @endif

                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-danger"
                                                        onclick="openDeleteModal({{ $equipment->id }}, @js($equipment->name))"
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

                @if ($equipments->hasPages())
                    <div class="mt-3">
                        {{ $equipments->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-pc-display"></i>
                    </div>

                    <h5 class="empty-state-title">No equipment found</h5>
                    <p class="empty-state-text mb-0">
                        Belum ada equipment yang tercatat. Tambahkan equipment baru untuk mulai mengelola aset operasional.
                    </p>

                    <div class="mt-3">
                        <button type="button" class="btn btn-primary btn-modern" onclick="openCreateModal()">
                            <i class="bi bi-plus-lg me-2"></i>Add Equipment
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Equipment Form Modal --}}
<div class="modal fade" id="equipmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form id="equipmentForm">
            @csrf
            <input type="hidden" id="equipment_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-1" id="equipmentModalTitle">Add Equipment</h5>
                        <div class="small text-muted">
                            Complete equipment identity, asset type, condition, status, assignment, and maintenance information.
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Basic Information</h5>
                                <p class="content-card-subtitle mb-0">
                                    Define equipment name, slug, generated code, type, and assigned user when needed.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">
                                        Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="name" class="form-control">
                                    <div class="invalid-feedback" id="error_name"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="slug" class="form-label">Slug</label>
                                    <input type="text" id="slug" class="form-control">
                                    <div class="form-text">Optional. Will auto-generate from name if empty.</div>
                                    <div class="invalid-feedback" id="error_slug"></div>
                                </div>

                                <div class="col-md-4">
                                    <label for="code_preview" class="form-label">Code</label>
                                    <input type="text" id="code_preview" class="form-control" readonly>
                                    <div class="form-text">Auto-generated by system.</div>
                                </div>

                                <div class="col-md-4">
                                    <label for="type" class="form-label">Asset Type</label>
                                    <select id="type" class="form-select">
                                        <option value="borrowable">Borrowable</option>
                                        <option value="assigned">Assigned</option>
                                    </select>
                                    <div class="invalid-feedback" id="error_type"></div>
                                </div>

                                <div class="col-md-4" id="assignedUserWrapper">
                                    <label for="assigned_user_id" class="form-label">Assigned User</label>
                                    <select id="assigned_user_id" class="form-select">
                                        <option value="">Select User</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">
                                                {{ $user->name }}{{ $user->email ? ' - ' . $user->email : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Used only for assigned asset type.</div>
                                    <div class="invalid-feedback" id="error_assigned_user_id"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Asset Detail</h5>
                                <p class="content-card-subtitle mb-0">
                                    Add serial number, location, brand, model, asset condition, availability status, and active status.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="serial_number" class="form-label">Serial Number</label>
                                    <input type="text" id="serial_number" class="form-control">
                                    <div class="invalid-feedback" id="error_serial_number"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" id="location" class="form-control" placeholder="e.g. Office Jakarta / Studio A">
                                    <div class="invalid-feedback" id="error_location"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="brand" class="form-label">Brand</label>
                                    <input type="text" id="brand" class="form-control">
                                    <div class="invalid-feedback" id="error_brand"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="model" class="form-label">Model</label>
                                    <input type="text" id="model" class="form-control">
                                    <div class="invalid-feedback" id="error_model"></div>
                                </div>

                                <div class="col-md-4">
                                    <label for="condition" class="form-label">Condition</label>
                                    <select id="condition" class="form-select">
                                        <option value="good">Good</option>
                                        <option value="minor_damage">Minor Damage</option>
                                        <option value="damaged">Damaged</option>
                                    </select>
                                    <div class="invalid-feedback" id="error_condition"></div>
                                </div>

                                <div class="col-md-4">
                                    <label for="status" class="form-label">Status</label>
                                    <select id="status" class="form-select">
                                        <option value="available">Available</option>
                                        <option value="borrowed">Borrowed</option>
                                        <option value="maintenance">Maintenance</option>
                                    </select>
                                    <div class="invalid-feedback" id="error_status"></div>
                                </div>

                                <div class="col-md-4">
                                    <label for="is_active" class="form-label">Status Active</label>
                                    <select id="is_active" class="form-select">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                    <div class="invalid-feedback" id="error_is_active"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Purchase & Maintenance</h5>
                                <p class="content-card-subtitle mb-0">
                                    Track purchase date, purchase price, and latest maintenance timestamp for asset history.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="purchase_date" class="form-label">Purchase Date</label>
                                    <input type="date" id="purchase_date" class="form-control">
                                    <div class="invalid-feedback" id="error_purchase_date"></div>
                                </div>

                                <div class="col-md-4">
                                    <label for="purchase_price" class="form-label">Purchase Price</label>
                                    <input type="number" min="0" step="0.01" id="purchase_price" class="form-control">
                                    <div class="invalid-feedback" id="error_purchase_price"></div>
                                </div>

                                <div class="col-md-4">
                                    <label for="last_maintenance_at" class="form-label">Last Maintenance</label>
                                    <input type="datetime-local" id="last_maintenance_at" class="form-control">
                                    <div class="invalid-feedback" id="error_last_maintenance_at"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Description</h5>
                                <p class="content-card-subtitle mb-0">
                                    Add optional description or important notes related to this equipment.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" rows="4" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_description"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-modern" id="submitBtn">
                        <span class="default-text">
                            <i class="bi bi-check-circle me-2"></i>Save
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

{{-- Borrow Modal --}}
<div class="modal fade" id="borrowModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="borrowForm">
            @csrf
            <input type="hidden" id="borrow_equipment_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-1">Borrow Equipment</h5>
                        <div class="small text-muted">
                            Assign this borrowable equipment to a borrower and define borrowing time.
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="borrowFormAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="content-card">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Borrowing Detail</h5>
                                <p class="content-card-subtitle mb-0">
                                    Select borrower, borrowed time, optional due date, and borrowing notes.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="mb-3">
                                <label for="borrow_equipment_name" class="form-label">Equipment</label>
                                <input type="text" id="borrow_equipment_name" class="form-control" readonly>
                            </div>

                            <div class="mb-3">
                                <label for="borrow_user_id" class="form-label">
                                    Borrower <span class="text-danger">*</span>
                                </label>
                                <select id="borrow_user_id" class="form-select">
                                    <option value="">Select User</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">
                                            {{ $user->name }}{{ $user->email ? ' - ' . $user->email : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="error_borrow_user_id"></div>
                            </div>

                            <div class="mb-3">
                                <label for="borrowed_at" class="form-label">
                                    Borrowed At <span class="text-danger">*</span>
                                </label>
                                <input type="datetime-local" id="borrowed_at" class="form-control">
                                <div class="invalid-feedback" id="error_borrowed_at"></div>
                            </div>

                            <div class="mb-3">
                                <label for="due_at" class="form-label">Due At</label>
                                <input type="datetime-local" id="due_at" class="form-control">
                                <div class="invalid-feedback" id="error_due_at"></div>
                            </div>

                            <div class="mb-0">
                                <label for="borrow_notes" class="form-label">Notes</label>
                                <textarea id="borrow_notes" rows="3" class="form-control"></textarea>
                                <div class="invalid-feedback" id="error_borrow_notes"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-modern" id="borrowSubmitBtn">
                        <span class="default-borrow-text">
                            <i class="bi bi-check-circle me-2"></i>Save
                        </span>
                        <span class="loading-borrow-text d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Return Modal --}}
<div class="modal fade" id="returnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="returnForm">
            @csrf
            <input type="hidden" id="return_borrowing_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-1">Return Equipment</h5>
                        <div class="small text-muted">
                            Record return time, condition after return, and return notes.
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="returnFormAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="content-card">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Return Detail</h5>
                                <p class="content-card-subtitle mb-0">
                                    Confirm returned equipment, return time, latest condition, and return notes.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="mb-3">
                                <label for="return_equipment_name" class="form-label">Equipment</label>
                                <input type="text" id="return_equipment_name" class="form-control" readonly>
                            </div>

                            <div class="mb-3">
                                <label for="returned_at" class="form-label">
                                    Returned At <span class="text-danger">*</span>
                                </label>
                                <input type="datetime-local" id="returned_at" class="form-control">
                                <div class="invalid-feedback" id="error_returned_at"></div>
                            </div>

                            <div class="mb-3">
                                <label for="return_condition" class="form-label">Condition After Return</label>
                                <select id="return_condition" class="form-select">
                                    <option value="good">Good</option>
                                    <option value="minor_damage">Minor Damage</option>
                                    <option value="damaged">Damaged</option>
                                </select>
                                <div class="invalid-feedback" id="error_return_condition"></div>
                            </div>

                            <div class="mb-0">
                                <label for="return_notes" class="form-label">Notes</label>
                                <textarea id="return_notes" rows="3" class="form-control"></textarea>
                                <div class="invalid-feedback" id="error_return_notes"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-modern" id="returnSubmitBtn">
                        <span class="default-return-text">
                            <i class="bi bi-check-circle me-2"></i>Save
                        </span>
                        <span class="loading-return-text d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- History Modal --}}
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-1">Equipment History</h5>
                    <div class="small text-muted">
                        Review borrowing or assignment history for the selected equipment.
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div id="historyContent">
                    <div class="text-center text-muted py-4">Loading...</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-1">Delete Equipment</h5>
                    <div class="small text-muted">
                        This action will remove selected equipment from the system.
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-danger mb-0">
                    <div class="d-flex gap-2 align-items-start">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div>
                            <div class="fw-semibold">Delete this equipment?</div>
                            <div class="small mt-1">
                                Are you sure you want to delete
                                <strong id="deleteEquipmentName"></strong>?
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger btn-modern" id="confirmDeleteBtn">
                    <span class="default-delete-text">
                        <i class="bi bi-trash me-2"></i>Delete
                    </span>
                    <span class="loading-delete-text d-none">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Deleting...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const equipmentModalEl = document.getElementById('equipmentModal');
    const equipmentModal = new bootstrap.Modal(equipmentModalEl);
    const equipmentForm = document.getElementById('equipmentForm');
    const submitBtn = document.getElementById('submitBtn');
    const modalTitle = document.getElementById('equipmentModalTitle');
    const formAlert = document.getElementById('formAlert');

    const borrowModalEl = document.getElementById('borrowModal');
    const borrowModal = new bootstrap.Modal(borrowModalEl);
    const borrowForm = document.getElementById('borrowForm');
    const borrowFormAlert = document.getElementById('borrowFormAlert');
    const borrowSubmitBtn = document.getElementById('borrowSubmitBtn');

    const returnModalEl = document.getElementById('returnModal');
    const returnModal = new bootstrap.Modal(returnModalEl);
    const returnForm = document.getElementById('returnForm');
    const returnFormAlert = document.getElementById('returnFormAlert');
    const returnSubmitBtn = document.getElementById('returnSubmitBtn');

    const historyModalEl = document.getElementById('historyModal');
    const historyModal = new bootstrap.Modal(historyModalEl);

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteEquipmentNameEl = document.getElementById('deleteEquipmentName');

    const fields = {
        id: document.getElementById('equipment_id'),
        name: document.getElementById('name'),
        slug: document.getElementById('slug'),
        code_preview: document.getElementById('code_preview'),
        serial_number: document.getElementById('serial_number'),
        brand: document.getElementById('brand'),
        model: document.getElementById('model'),
        description: document.getElementById('description'),
        condition: document.getElementById('condition'),
        status: document.getElementById('status'),
        type: document.getElementById('type'),
        assigned_user_id: document.getElementById('assigned_user_id'),
        location: document.getElementById('location'),
        purchase_date: document.getElementById('purchase_date'),
        purchase_price: document.getElementById('purchase_price'),
        last_maintenance_at: document.getElementById('last_maintenance_at'),
        is_active: document.getElementById('is_active'),
    };

    let isEditMode = false;
    let autoSlug = true;
    let deleteEquipmentId = null;
    let reloadTimeout = null;

    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const id = 'toast-' + Date.now();

        const bgClass = {
            success: 'bg-success',
            danger: 'bg-danger',
            warning: 'bg-warning text-dark',
            info: 'bg-info text-dark'
        }[type] || 'bg-success';

        const closeBtnClass = type === 'warning' || type === 'info'
            ? 'btn-close'
            : 'btn-close btn-close-white';

        const html = `
            <div id="${id}" class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="${closeBtnClass} me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);

        const toastEl = document.getElementById(id);
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });

        toast.show();

        toastEl.addEventListener('hidden.bs.toast', () => {
            toastEl.remove();
        });
    }

    function scheduleReload() {
        if (reloadTimeout) {
            clearTimeout(reloadTimeout);
        }

        reloadTimeout = setTimeout(() => {
            location.reload();
        }, 1500);
    }

    function slugify(text) {
        return String(text || '')
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }

    function toDatetimeLocalValue(date = new Date()) {
        const offset = date.getTimezoneOffset();
        const localDate = new Date(date.getTime() - (offset * 60000));
        return localDate.toISOString().slice(0, 16);
    }

    function formatDateTime(value) {
        if (!value) return '-';

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return value;

        return new Intl.DateTimeFormat('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(date);
    }

    function clearValidationErrors() {
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
    }

    function setValidationErrors(errors = {}) {
        clearValidationErrors();

        Object.keys(errors).forEach(key => {
            const field = document.getElementById(key) || fields[key];
            const errorEl = document.getElementById(`error_${key}`);

            if (field && field.classList) {
                field.classList.add('is-invalid');
            }

            if (errorEl) {
                errorEl.textContent = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
            }
        });
    }

    function setSubmitLoading(isLoading) {
        submitBtn.disabled = isLoading;
        submitBtn.querySelector('.default-text').classList.toggle('d-none', isLoading);
        submitBtn.querySelector('.loading-text').classList.toggle('d-none', !isLoading);
    }

    function setBorrowLoading(isLoading) {
        borrowSubmitBtn.disabled = isLoading;
        borrowSubmitBtn.querySelector('.default-borrow-text').classList.toggle('d-none', isLoading);
        borrowSubmitBtn.querySelector('.loading-borrow-text').classList.toggle('d-none', !isLoading);
    }

    function setReturnLoading(isLoading) {
        returnSubmitBtn.disabled = isLoading;
        returnSubmitBtn.querySelector('.default-return-text').classList.toggle('d-none', isLoading);
        returnSubmitBtn.querySelector('.loading-return-text').classList.toggle('d-none', !isLoading);
    }

    function setDeleteLoading(isLoading) {
        confirmDeleteBtn.disabled = isLoading;
        confirmDeleteBtn.querySelector('.default-delete-text').classList.toggle('d-none', isLoading);
        confirmDeleteBtn.querySelector('.loading-delete-text').classList.toggle('d-none', !isLoading);
    }

    function toggleAssignedField() {
        const type = fields.type.value;
        const assignedWrapper = document.getElementById('assignedUserWrapper');

        if (type === 'assigned') {
            assignedWrapper.classList.remove('d-none');
        } else {
            assignedWrapper.classList.add('d-none');
            fields.assigned_user_id.value = '';
        }
    }

    function resetForm() {
        equipmentForm.reset();
        fields.id.value = '';
        fields.code_preview.value = 'Auto generated';
        fields.condition.value = 'good';
        fields.status.value = 'available';
        fields.type.value = 'borrowable';
        fields.assigned_user_id.value = '';
        fields.location.value = '';
        fields.purchase_date.value = '';
        fields.purchase_price.value = '';
        fields.last_maintenance_at.value = '';
        fields.is_active.value = '1';
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';
        clearValidationErrors();
        setSubmitLoading(false);
        autoSlug = true;
        toggleAssignedField();
    }

    function resetBorrowForm() {
        borrowForm.reset();
        document.getElementById('borrow_equipment_id').value = '';
        document.getElementById('borrow_equipment_name').value = '';
        document.getElementById('borrow_user_id').value = '';
        document.getElementById('borrowed_at').value = toDatetimeLocalValue();
        document.getElementById('due_at').value = '';
        borrowFormAlert.classList.add('d-none');
        borrowFormAlert.innerHTML = '';
        clearValidationErrors();
        setBorrowLoading(false);
    }

    function resetReturnForm() {
        returnForm.reset();
        document.getElementById('return_borrowing_id').value = '';
        document.getElementById('return_equipment_name').value = '';
        document.getElementById('returned_at').value = toDatetimeLocalValue();
        document.getElementById('return_condition').value = 'good';
        returnFormAlert.classList.add('d-none');
        returnFormAlert.innerHTML = '';
        clearValidationErrors();
        setReturnLoading(false);
    }

    function openCreateModal() {
        isEditMode = false;
        resetForm();
        modalTitle.textContent = 'Add Equipment';
        equipmentModal.show();
    }

    async function editEquipment(id) {
        isEditMode = true;
        resetForm();
        modalTitle.textContent = 'Edit Equipment';

        try {
            const response = await fetch(`/equipment/${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch equipment data.');
            }

            const data = result.data;

            fields.id.value = data.id;
            fields.name.value = data.name ?? '';
            fields.slug.value = data.slug ?? '';
            fields.code_preview.value = data.code ?? '-';
            fields.serial_number.value = data.serial_number ?? '';
            fields.brand.value = data.brand ?? '';
            fields.model.value = data.model ?? '';
            fields.description.value = data.description ?? '';
            fields.condition.value = data.condition ?? 'good';
            fields.status.value = data.status ?? 'available';
            fields.type.value = data.type ?? 'borrowable';
            fields.assigned_user_id.value = data.assigned_user_id ?? '';
            fields.location.value = data.location ?? '';
            fields.purchase_date.value = data.purchase_date ?? '';
            fields.purchase_price.value = data.purchase_price ?? '';
            fields.last_maintenance_at.value = data.last_maintenance_at
                ? toDatetimeLocalValue(new Date(data.last_maintenance_at))
                : '';
            fields.is_active.value = data.is_active ? '1' : '0';

            autoSlug = false;
            toggleAssignedField();
            equipmentModal.show();
        } catch (error) {
            formAlert.classList.remove('d-none');
            formAlert.innerHTML = error.message || 'Failed to load equipment data.';
            equipmentModal.show();
        }
    }

    async function openHistoryModal(id) {
        const container = document.getElementById('historyContent');
        container.innerHTML = '<div class="text-center text-muted py-4">Loading...</div>';
        historyModal.show();

        try {
            const response = await fetch(`/equipment/${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch equipment history.');
            }

            const data = result.data;
            let html = '';

            html += `
                <div class="content-card mb-3">
                    <div class="content-card-body">
                        <div class="fw-semibold fs-6">${data.name ?? '-'}</div>
                        <small class="text-muted">
                            ${data.code ?? '-'} · ${(data.type ?? 'borrowable') === 'assigned' ? 'Assigned Asset' : 'Borrowable Asset'}
                        </small>
                    </div>
                </div>
            `;

            if ((data.type ?? 'borrowable') === 'borrowable') {
                html += `<h6 class="mb-3">Borrowing History</h6>`;

                if (!data.borrowings || data.borrowings.length === 0) {
                    html += `
                        <div class="empty-state-box">
                            <div class="empty-state-icon">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <h5 class="empty-state-title">No borrowing history found</h5>
                            <p class="empty-state-text mb-0">
                                This equipment has no borrowing history yet.
                            </p>
                        </div>
                    `;
                } else {
                    html += `<div class="list-group">`;

                    data.borrowings.forEach(item => {
                        html += `
                            <div class="list-group-item">
                                <div class="fw-semibold">${item.user?.name ?? '-'}</div>
                                <small class="text-muted d-block">Borrowed: ${formatDateTime(item.borrowed_at)}</small>
                                <small class="text-muted d-block">Expected Return: ${formatDateTime(item.expected_return_at)}</small>
                                <small class="text-muted d-block">Returned: ${formatDateTime(item.returned_at)}</small>
                                <small class="text-muted d-block">Status: ${item.status ?? '-'}</small>
                                ${item.notes ? `<div class="small mt-2">Notes: ${item.notes}</div>` : ''}
                                ${item.return_notes ? `<div class="small">Return Notes: ${item.return_notes}</div>` : ''}
                            </div>
                        `;
                    });

                    html += `</div>`;
                }
            } else {
                html += `<h6 class="mb-3">Assignment History</h6>`;

                if (!data.assignments || data.assignments.length === 0) {
                    html += `
                        <div class="empty-state-box">
                            <div class="empty-state-icon">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <h5 class="empty-state-title">No assignment history found</h5>
                            <p class="empty-state-text mb-0">
                                This equipment has no assignment history yet.
                            </p>
                        </div>
                    `;
                } else {
                    html += `<div class="list-group">`;

                    data.assignments.forEach(item => {
                        html += `
                            <div class="list-group-item">
                                <div class="fw-semibold">${item.user?.name ?? '-'}</div>
                                <small class="text-muted d-block">Assigned: ${formatDateTime(item.assigned_at)}</small>
                                <small class="text-muted d-block">Released: ${item.unassigned_at ? formatDateTime(item.unassigned_at) : 'Current Holder'}</small>
                                ${item.notes ? `<div class="small mt-2">Notes: ${item.notes}</div>` : ''}
                            </div>
                        `;
                    });

                    html += `</div>`;
                }
            }

            container.innerHTML = html;
        } catch (error) {
            container.innerHTML = `<div class="text-danger">${error.message || 'Failed to load history.'}</div>`;
        }
    }

    function openBorrowModal(id, name) {
        resetBorrowForm();
        document.getElementById('borrow_equipment_id').value = id;
        document.getElementById('borrow_equipment_name').value = name || '-';
        borrowModal.show();
    }

    function openReturnModal(id, name, condition) {
        resetReturnForm();
        document.getElementById('return_borrowing_id').value = id;
        document.getElementById('return_equipment_name').value = name || '-';
        document.getElementById('return_condition').value = condition || 'good';
        returnModal.show();
    }

    function openDeleteModal(id, name) {
        deleteEquipmentId = id;
        deleteEquipmentNameEl.textContent = name || '-';
        setDeleteLoading(false);
        deleteModal.show();
    }

    fields.name.addEventListener('input', function () {
        if (!isEditMode && autoSlug) {
            fields.slug.value = slugify(this.value);
        }
    });

    fields.slug.addEventListener('input', function () {
        autoSlug = this.value.trim() === '';
    });

    fields.type.addEventListener('change', toggleAssignedField);

    equipmentForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';

        const id = fields.id.value;
        const url = id ? `/equipment/${id}` : `/equipment`;

        const payload = {
            name: fields.name.value.trim(),
            slug: fields.slug.value.trim(),
            serial_number: fields.serial_number.value.trim(),
            brand: fields.brand.value.trim(),
            model: fields.model.value.trim(),
            description: fields.description.value.trim(),
            condition: fields.condition.value,
            status: fields.status.value,
            type: fields.type.value,
            assigned_user_id: fields.assigned_user_id.value || null,
            location: fields.location.value.trim(),
            purchase_date: fields.purchase_date.value || null,
            purchase_price: fields.purchase_price.value || null,
            last_maintenance_at: fields.last_maintenance_at.value || null,
            is_active: fields.is_active.value === '1' ? 1 : 0,
        };

        setSubmitLoading(true);

        try {
            const response = await fetch(url, {
                method: id ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
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
                throw new Error(result.message || 'Failed to save equipment.');
            }

            equipmentModal.hide();
            showToast(result.message || 'Equipment saved successfully', 'success');
            scheduleReload();
        } catch (error) {
            if (error.message !== 'Validation failed.') {
                formAlert.classList.remove('d-none');
                formAlert.innerHTML = error.message || 'Something went wrong.';
            }
        } finally {
            setSubmitLoading(false);
        }
    });

    borrowForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();
        borrowFormAlert.classList.add('d-none');
        borrowFormAlert.innerHTML = '';

        const equipmentId = document.getElementById('borrow_equipment_id').value;

        const payload = {
            user_id: document.getElementById('borrow_user_id').value,
            borrowed_at: document.getElementById('borrowed_at').value,
            due_at: document.getElementById('due_at').value,
            notes: document.getElementById('borrow_notes').value.trim(),
        };

        setBorrowLoading(true);

        try {
            const response = await fetch(`/equipment/${equipmentId}/borrow`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
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
                throw new Error(result.message || 'Failed to borrow equipment.');
            }

            borrowModal.hide();
            showToast(result.message || 'Equipment borrowed successfully', 'success');
            scheduleReload();
        } catch (error) {
            if (error.message !== 'Validation failed.') {
                borrowFormAlert.classList.remove('d-none');
                borrowFormAlert.innerHTML = error.message || 'Something went wrong.';
            }
        } finally {
            setBorrowLoading(false);
        }
    });

    returnForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();
        returnFormAlert.classList.add('d-none');
        returnFormAlert.innerHTML = '';

        const borrowingId = document.getElementById('return_borrowing_id').value;

        const payload = {
            returned_at: document.getElementById('returned_at').value,
            condition: document.getElementById('return_condition').value,
            notes: document.getElementById('return_notes').value.trim(),
        };

        setReturnLoading(true);

        try {
            const response = await fetch(`/equipment/borrowings/${borrowingId}/return`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
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
                throw new Error(result.message || 'Failed to return equipment.');
            }

            returnModal.hide();
            showToast(result.message || 'Equipment returned successfully', 'success');
            scheduleReload();
        } catch (error) {
            if (error.message !== 'Validation failed.') {
                returnFormAlert.classList.remove('d-none');
                returnFormAlert.innerHTML = error.message || 'Something went wrong.';
            }
        } finally {
            setReturnLoading(false);
        }
    });

    confirmDeleteBtn.addEventListener('click', async function () {
        if (!deleteEquipmentId) return;

        setDeleteLoading(true);

        try {
            const response = await fetch(`/equipment/${deleteEquipmentId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete equipment.');
            }

            deleteModal.hide();
            showToast(result.message || 'Equipment deleted successfully', 'danger');
            scheduleReload();
        } catch (error) {
            showToast(error.message || 'Failed to delete equipment.', 'danger');
        } finally {
            setDeleteLoading(false);
            deleteEquipmentId = null;
        }
    });

    equipmentModalEl.addEventListener('hidden.bs.modal', function () {
        resetForm();
    });

    borrowModalEl.addEventListener('hidden.bs.modal', function () {
        resetBorrowForm();
    });

    returnModalEl.addEventListener('hidden.bs.modal', function () {
        resetReturnForm();
    });

    deleteModalEl.addEventListener('hidden.bs.modal', function () {
        deleteEquipmentId = null;
        deleteEquipmentNameEl.textContent = '';
        setDeleteLoading(false);
    });

    toggleAssignedField();
</script>
@endpush