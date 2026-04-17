@extends('layouts.app-dashboard')

@section('title', 'Marketing Plans')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Marketing Plans</h4>
            <small class="text-muted">Manage strategic marketing plans by period, objective, and budget</small>
        </div>

        <button type="button" class="btn btn-primary" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Plan
        </button>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('marketing.plans.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input
                        type="text"
                        name="search"
                        class="form-control form-control-sm"
                        placeholder="Search title or objective..."
                        value="{{ request('search') }}"
                    >
                </div>

                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="period_type" class="form-select form-select-sm">
                        <option value="">All Periods</option>
                        <option value="weekly" {{ request('period_type') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                        <option value="monthly" {{ request('period_type') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="quarterly" {{ request('period_type') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                    </select>
                </div>

                <div class="col-md-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                </div>

                <div class="col-md-auto">
                    <a href="{{ route('marketing.plans.index') }}" class="btn btn-sm btn-light border">
                        Reset
                    </a>
                </div>

                <div class="col-md-auto ms-md-auto">
                    <div class="d-flex align-items-center gap-2">
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
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;">No</th>
                        <th>Title</th>
                        <th style="width: 120px;">Period</th>
                        <th>Objective</th>
                        <th style="width: 170px;">Date Range</th>
                        <th style="width: 140px;">Budget</th>
                        <th style="width: 120px;">Status</th>
                        <th style="width: 120px;">Active</th>
                        <th style="width: 170px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plans as $plan)
                        @php
                            $statusClass = match($plan->status) {
                                'draft' => 'secondary',
                                'active' => 'primary',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'secondary',
                            };

                            $planPayload = [
                                'id' => $plan->id,
                                'title' => $plan->title,
                                'period_type' => $plan->period_type,
                                'start_date' => optional($plan->start_date)->format('Y-m-d'),
                                'end_date' => optional($plan->end_date)->format('Y-m-d'),
                                'objective' => $plan->objective,
                                'strategy' => $plan->strategy,
                                'notes' => $plan->notes,
                                'budget' => $plan->budget,
                                'status' => $plan->status,
                                'is_active' => (bool) $plan->is_active,
                            ];
                        @endphp

                        <tr>
                            <td>
                                {{ ($plans->currentPage() - 1) * $plans->perPage() + $loop->iteration }}
                            </td>

                            <td>
                                <div class="fw-semibold">{{ $plan->title }}</div>
                                <small class="text-muted">
                                    {{ $plan->strategy ? \Illuminate\Support\Str::limit($plan->strategy, 70) : '-' }}
                                </small>
                            </td>

                            <td>
                                <span class="text-capitalize">{{ $plan->period_type }}</span>
                            </td>

                            <td>
                                {{ $plan->objective ?: '-' }}
                            </td>

                            <td class="text-muted small">
                                @if ($plan->start_date && $plan->end_date)
                                    {{ $plan->start_date->format('d M Y') }}<br>
                                    <span class="text-muted">to {{ $plan->end_date->format('d M Y') }}</span>
                                @elseif ($plan->start_date)
                                    {{ $plan->start_date->format('d M Y') }}
                                @elseif ($plan->end_date)
                                    {{ $plan->end_date->format('d M Y') }}
                                @else
                                    -
                                @endif
                            </td>

                            <td class="fw-semibold">
                                Rp {{ number_format((float) $plan->budget, 0, ',', '.') }}
                            </td>

                            <td>
                                <span class="badge bg-{{ $statusClass }}">{{ ucfirst($plan->status) }}</span>
                            </td>

                            <td>
                                @if ($plan->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <div class="d-inline-flex gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-plan="{{ e(json_encode($planPayload)) }}"
                                        onclick="editPlan(this)"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="openDeleteModal({{ $plan->id }}, @js($plan->title))"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                No marketing plans found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($plans->hasPages())
            <div class="card-footer bg-white">
                {{ $plans->links() }}
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="planModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="planForm">
            @csrf
            <input type="hidden" id="plan_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="planModalTitle">Add Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="title" class="form-label">
                                Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="title" class="form-control">
                            <div class="invalid-feedback" id="error_title"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="period_type" class="form-label">
                                Period Type <span class="text-danger">*</span>
                            </label>
                            <select id="period_type" class="form-select">
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                            </select>
                            <div class="invalid-feedback" id="error_period_type"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" id="start_date" class="form-control">
                            <div class="invalid-feedback" id="error_start_date"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" id="end_date" class="form-control">
                            <div class="invalid-feedback" id="error_end_date"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="objective" class="form-label">Objective</label>
                            <input type="text" id="objective" class="form-control" placeholder="Awareness / Leads / Conversion">
                            <div class="invalid-feedback" id="error_objective"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="budget" class="form-label">Budget</label>
                            <input type="number" id="budget" class="form-control" min="0" step="0.01">
                            <div class="invalid-feedback" id="error_budget"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select id="status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <div class="invalid-feedback" id="error_status"></div>
                        </div>

                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                            <div class="invalid-feedback d-block" id="error_is_active"></div>
                        </div>

                        <div class="col-12">
                            <label for="strategy" class="form-label">Strategy</label>
                            <textarea id="strategy" rows="4" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_strategy"></div>
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" rows="3" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_notes"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span class="default-text">Save</span>
                        <span class="loading-text d-none">Saving...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Delete Marketing Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to delete
                    <strong id="deletePlanTitle"></strong>?
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <span class="default-delete-text">Delete</span>
                    <span class="loading-delete-text d-none">Deleting...</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const planModalEl = document.getElementById('planModal');
    const planModal = new bootstrap.Modal(planModalEl);
    const planForm = document.getElementById('planForm');
    const submitBtn = document.getElementById('submitBtn');
    const modalTitle = document.getElementById('planModalTitle');
    const formAlert = document.getElementById('formAlert');

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deletePlanTitleEl = document.getElementById('deletePlanTitle');

    const fields = {
        id: document.getElementById('plan_id'),
        title: document.getElementById('title'),
        period_type: document.getElementById('period_type'),
        start_date: document.getElementById('start_date'),
        end_date: document.getElementById('end_date'),
        objective: document.getElementById('objective'),
        strategy: document.getElementById('strategy'),
        notes: document.getElementById('notes'),
        budget: document.getElementById('budget'),
        status: document.getElementById('status'),
        is_active: document.getElementById('is_active'),
    };

    let isEditMode = false;
    let deletePlanId = null;
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

        const html = `
            <div id="${id}" class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);

        const toastEl = document.getElementById(id);
        const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
        toast.show();

        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    function resetValidation() {
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';

        Object.keys(fields).forEach((key) => {
            if (fields[key] && fields[key].classList) {
                fields[key].classList.remove('is-invalid');
            }

            const errorEl = document.getElementById(`error_${key}`);
            if (errorEl) {
                errorEl.textContent = '';
            }
        });
    }

    function resetForm() {
        resetValidation();

        fields.id.value = '';
        fields.title.value = '';
        fields.period_type.value = 'monthly';
        fields.start_date.value = '';
        fields.end_date.value = '';
        fields.objective.value = '';
        fields.strategy.value = '';
        fields.notes.value = '';
        fields.budget.value = '';
        fields.status.value = 'draft';
        fields.is_active.checked = true;
    }

    function setLoading(button, isLoading, defaultSelector, loadingSelector) {
        button.disabled = isLoading;
        button.querySelector(defaultSelector).classList.toggle('d-none', isLoading);
        button.querySelector(loadingSelector).classList.toggle('d-none', !isLoading);
    }

    function openCreateModal() {
        isEditMode = false;
        resetForm();
        modalTitle.textContent = 'Add Plan';
        planModal.show();
    }

    function editPlan(button) {
        isEditMode = true;
        resetForm();

        const plan = JSON.parse(button.dataset.plan || '{}');

        modalTitle.textContent = 'Edit Plan';
        fields.id.value = plan.id ?? '';
        fields.title.value = plan.title ?? '';
        fields.period_type.value = plan.period_type ?? 'monthly';
        fields.start_date.value = plan.start_date ?? '';
        fields.end_date.value = plan.end_date ?? '';
        fields.objective.value = plan.objective ?? '';
        fields.strategy.value = plan.strategy ?? '';
        fields.notes.value = plan.notes ?? '';
        fields.budget.value = plan.budget ?? '';
        fields.status.value = plan.status ?? 'draft';
        fields.is_active.checked = !!plan.is_active;

        planModal.show();
    }

    async function submitForm(event) {
        event.preventDefault();
        resetValidation();

        const id = fields.id.value;
        const url = isEditMode
            ? `{{ url('/marketing/plans') }}/${id}`
            : `{{ route('marketing.plans.store') }}`;

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');

        if (isEditMode) {
            formData.append('_method', 'PUT');
        }

        formData.append('title', fields.title.value);
        formData.append('period_type', fields.period_type.value);
        formData.append('start_date', fields.start_date.value);
        formData.append('end_date', fields.end_date.value);
        formData.append('objective', fields.objective.value);
        formData.append('strategy', fields.strategy.value);
        formData.append('notes', fields.notes.value);
        formData.append('budget', fields.budget.value);
        formData.append('status', fields.status.value);

        if (fields.is_active.checked) {
            formData.append('is_active', '1');
        }

        setLoading(submitBtn, true, '.default-text', '.loading-text');

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                if (response.status === 422 && data.errors) {
                    Object.entries(data.errors).forEach(([field, messages]) => {
                        if (fields[field]) {
                            fields[field].classList.add('is-invalid');
                        }

                        const errorEl = document.getElementById(`error_${field}`);
                        if (errorEl) {
                            errorEl.textContent = messages[0];
                        }
                    });

                    formAlert.classList.remove('d-none');
                    formAlert.textContent = data.message || 'Please check the form fields.';
                    return;
                }

                throw new Error(data.message || 'Failed to save plan.');
            }

            planModal.hide();
            showToast(data.message || (isEditMode ? 'Plan updated successfully.' : 'Plan created successfully.'));

            clearTimeout(reloadTimeout);
            reloadTimeout = setTimeout(() => window.location.reload(), 700);
        } catch (error) {
            formAlert.classList.remove('d-none');
            formAlert.textContent = error.message || 'Something went wrong.';
        } finally {
            setLoading(submitBtn, false, '.default-text', '.loading-text');
        }
    }

    function openDeleteModal(id, title) {
        deletePlanId = id;
        deletePlanTitleEl.textContent = title;
        deleteModal.show();
    }

    async function confirmDelete() {
        if (!deletePlanId) return;

        setLoading(confirmDeleteBtn, true, '.default-delete-text', '.loading-delete-text');

        try {
            const response = await fetch(`{{ url('/marketing/plans') }}/${deletePlanId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: new URLSearchParams({
                    _method: 'DELETE',
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to delete plan.');
            }

            deleteModal.hide();
            showToast(data.message || 'Plan deleted successfully.');

            clearTimeout(reloadTimeout);
            reloadTimeout = setTimeout(() => window.location.reload(), 700);
        } catch (error) {
            showToast(error.message || 'Something went wrong.', 'danger');
        } finally {
            setLoading(confirmDeleteBtn, false, '.default-delete-text', '.loading-delete-text');
        }
    }

    planForm.addEventListener('submit', submitForm);
    confirmDeleteBtn.addEventListener('click', confirmDelete);
    planModalEl.addEventListener('hidden.bs.modal', resetForm);
</script>
@endpush