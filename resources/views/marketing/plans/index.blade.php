@extends('layouts.app-dashboard')

@section('title', 'Marketing Plans')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Marketing Plans</h4>
            <small class="text-muted">Manage strategic marketing plans by period, objective, and budget.</small>
        </div>

        <button type="button" class="btn btn-primary" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Plan
        </button>
    </div>

    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;"></div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('marketing.plans.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input
                        type="text"
                        name="search"
                        class="form-control form-control-sm"
                        placeholder="Search title, objective, strategy..."
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
                        <option value="yearly" {{ request('period_type') === 'yearly' ? 'selected' : '' }}>Yearly</option>
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
                        <th style="width: 160px;" class="text-center">Action</th>
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
                                {{ $plan->objective ? \Illuminate\Support\Str::limit($plan->objective, 100) : '-' }}
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
                                        data-id="{{ $plan->id }}"
                                        data-title="{{ e($plan->title) }}"
                                        data-period_type="{{ e($plan->period_type) }}"
                                        data-start_date="{{ optional($plan->start_date)->format('Y-m-d') }}"
                                        data-end_date="{{ optional($plan->end_date)->format('Y-m-d') }}"
                                        data-objective="{{ e($plan->objective ?? '') }}"
                                        data-strategy="{{ e($plan->strategy ?? '') }}"
                                        data-notes="{{ e($plan->notes ?? '') }}"
                                        data-budget="{{ $plan->budget }}"
                                        data-status="{{ e($plan->status) }}"
                                        data-is_active="{{ $plan->is_active ? 1 : 0 }}"
                                        onclick="editPlan(this)"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-id="{{ $plan->id }}"
                                        data-title="{{ e($plan->title) }}"
                                        onclick="openDeleteModal(this)"
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

{{-- Form Modal --}}
<div class="modal fade" id="planModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="planForm">
            @csrf
            <input type="hidden" id="plan_id" name="plan_id">
            <input type="hidden" id="form_method" name="_method" value="POST">

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
                            <input
                                type="text"
                                id="title"
                                name="title"
                                class="form-control"
                                placeholder="Contoh: April Lead Generation Plan"
                            >
                            <div class="invalid-feedback" id="error_title"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="period_type" class="form-label">
                                Period Type <span class="text-danger">*</span>
                            </label>
                            <select
                                id="period_type"
                                name="period_type"
                                class="form-select"
                            >
                                <option value="">Select period type</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                            <div class="invalid-feedback" id="error_period_type"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input
                                type="date"
                                id="start_date"
                                name="start_date"
                                class="form-control"
                            >
                            <div class="invalid-feedback" id="error_start_date"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="end_date" class="form-label">End Date</label>
                            <input
                                type="date"
                                id="end_date"
                                name="end_date"
                                class="form-control"
                            >
                            <div class="invalid-feedback" id="error_end_date"></div>
                        </div>

                        <div class="col-12">
                            <label for="objective" class="form-label">Objective</label>
                            <textarea
                                id="objective"
                                name="objective"
                                rows="4"
                                class="form-control"
                                placeholder="Contoh: Generate 150 leads dan mencapai minimal 15 conversion dengan CPL di bawah Rp30.000."
                            ></textarea>
                            <div class="invalid-feedback" id="error_objective"></div>
                        </div>

                        <div class="col-12">
                            <label for="strategy" class="form-label">Strategy</label>
                            <textarea
                                id="strategy"
                                name="strategy"
                                rows="4"
                                class="form-control"
                                placeholder="Contoh: Menggunakan kombinasi Meta Ads dan TikTok Ads untuk lead generation, webinar sebagai conversion booster, lalu retargeting pada minggu ke-3."
                            ></textarea>
                            <div class="invalid-feedback" id="error_strategy"></div>
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea
                                id="notes"
                                name="notes"
                                rows="3"
                                class="form-control"
                                placeholder="Catatan tambahan untuk tim marketing atau sales"
                            ></textarea>
                            <div class="invalid-feedback" id="error_notes"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="budget" class="form-label">Budget</label>
                            <input
                                type="number"
                                id="budget"
                                name="budget"
                                class="form-control"
                                min="0"
                                step="0.01"
                                placeholder="0"
                            >
                            <div class="invalid-feedback" id="error_budget"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select
                                id="status"
                                name="status"
                                class="form-select"
                            >
                                <option value="">Select status</option>
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <div class="invalid-feedback" id="error_status"></div>
                        </div>

                        <div class="col-12">
                            <div class="form-check mt-1">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="is_active"
                                    name="is_active"
                                    value="1"
                                    checked
                                >
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                            <div class="invalid-feedback d-block" id="error_is_active"></div>
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

{{-- Delete Modal --}}
<div class="modal fade" id="deletePlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="deletePlanForm">
            @csrf
            <input type="hidden" name="_method" value="DELETE">
            <input type="hidden" id="delete_plan_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="deleteAlert" class="alert alert-danger d-none mb-3"></div>
                    <p class="mb-0">
                        Are you sure you want to delete
                        <strong id="delete_plan_title">this plan</strong>?
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-danger" id="deleteSubmitBtn">
                        <span class="default-text">Delete</span>
                        <span class="loading-text d-none">Deleting...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const storeUrl = @json(route('marketing.plans.store'));
    const baseUrl = @json(url('/marketing/plans'));
    const csrfToken = @json(csrf_token());

    let planModal;
    let deletePlanModal;

    document.addEventListener('DOMContentLoaded', function () {
        planModal = new bootstrap.Modal(document.getElementById('planModal'));
        deletePlanModal = new bootstrap.Modal(document.getElementById('deletePlanModal'));

        document.getElementById('planForm').addEventListener('submit', submitPlanForm);
        document.getElementById('deletePlanForm').addEventListener('submit', submitDeleteForm);
    });

    function openCreateModal() {
        resetPlanForm();
        document.getElementById('planModalTitle').textContent = 'Add Plan';
        document.getElementById('form_method').value = 'POST';
        document.getElementById('is_active').checked = true;
        planModal.show();
    }

    function editPlan(button) {
        resetPlanForm();

        document.getElementById('planModalTitle').textContent = 'Edit Plan';
        document.getElementById('form_method').value = 'PUT';
        document.getElementById('plan_id').value = button.dataset.id || '';

        document.getElementById('title').value = button.dataset.title || '';
        document.getElementById('period_type').value = button.dataset.period_type || '';
        document.getElementById('start_date').value = button.dataset.start_date || '';
        document.getElementById('end_date').value = button.dataset.end_date || '';
        document.getElementById('objective').value = button.dataset.objective || '';
        document.getElementById('strategy').value = button.dataset.strategy || '';
        document.getElementById('notes').value = button.dataset.notes || '';
        document.getElementById('budget').value = button.dataset.budget || '';
        document.getElementById('status').value = button.dataset.status || '';
        document.getElementById('is_active').checked = String(button.dataset.is_active) === '1';

        planModal.show();
    }

    function openDeleteModal(button) {
        resetDeleteForm();

        document.getElementById('delete_plan_id').value = button.dataset.id || '';
        document.getElementById('delete_plan_title').textContent = button.dataset.title || 'this plan';

        deletePlanModal.show();
    }

    function resetPlanForm() {
        const form = document.getElementById('planForm');
        form.reset();

        document.getElementById('plan_id').value = '';
        document.getElementById('form_method').value = 'POST';
        document.getElementById('formAlert').classList.add('d-none');
        document.getElementById('formAlert').textContent = '';

        clearValidationErrors(form);
    }

    function resetDeleteForm() {
        document.getElementById('delete_plan_id').value = '';
        document.getElementById('deleteAlert').classList.add('d-none');
        document.getElementById('deleteAlert').textContent = '';
    }

    function clearValidationErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        [
            'title',
            'period_type',
            'start_date',
            'end_date',
            'objective',
            'strategy',
            'notes',
            'budget',
            'status',
            'is_active'
        ].forEach(field => {
            const errorEl = document.getElementById(`error_${field}`);
            if (errorEl) {
                errorEl.textContent = '';
            }
        });
    }

    function setButtonLoading(buttonId, isLoading) {
        const button = document.getElementById(buttonId);
        if (!button) return;

        const defaultText = button.querySelector('.default-text');
        const loadingText = button.querySelector('.loading-text');

        button.disabled = isLoading;

        if (defaultText) {
            defaultText.classList.toggle('d-none', isLoading);
        }

        if (loadingText) {
            loadingText.classList.toggle('d-none', !isLoading);
        }
    }

    async function submitPlanForm(event) {
        event.preventDefault();

        const form = document.getElementById('planForm');
        const planId = document.getElementById('plan_id').value;
        const method = document.getElementById('form_method').value;
        const url = method === 'PUT' && planId ? `${baseUrl}/${planId}` : storeUrl;

        clearValidationErrors(form);
        document.getElementById('formAlert').classList.add('d-none');
        document.getElementById('formAlert').textContent = '';

        const formData = new FormData(form);

        if (!document.getElementById('is_active').checked) {
            formData.delete('is_active');
        }

        setButtonLoading('submitBtn', true);

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await response.json();

            if (!response.ok) {
                if (response.status === 422 && data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        const input = form.querySelector(`[name="${field}"]`);
                        const errorEl = document.getElementById(`error_${field}`);

                        if (input) {
                            input.classList.add('is-invalid');
                        }

                        if (errorEl) {
                            errorEl.textContent = data.errors[field][0];
                        }
                    });
                } else {
                    const alertEl = document.getElementById('formAlert');
                    alertEl.classList.remove('d-none');
                    alertEl.textContent = data.message || 'Failed to save marketing plan.';
                }

                return;
            }

            planModal.hide();
            showToast(data.message || 'Marketing plan saved successfully.', 'success');

            setTimeout(() => {
                window.location.reload();
            }, 700);
        } catch (error) {
            const alertEl = document.getElementById('formAlert');
            alertEl.classList.remove('d-none');
            alertEl.textContent = 'Network error. Please try again.';
        } finally {
            setButtonLoading('submitBtn', false);
        }
    }

    async function submitDeleteForm(event) {
        event.preventDefault();

        const planId = document.getElementById('delete_plan_id').value;
        const alertEl = document.getElementById('deleteAlert');

        alertEl.classList.add('d-none');
        alertEl.textContent = '';

        if (!planId) {
            alertEl.classList.remove('d-none');
            alertEl.textContent = 'Plan ID not found.';
            return;
        }

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('_method', 'DELETE');

        setButtonLoading('deleteSubmitBtn', true);

        try {
            const response = await fetch(`${baseUrl}/${planId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await response.json();

            if (!response.ok) {
                alertEl.classList.remove('d-none');
                alertEl.textContent = data.message || 'Failed to delete marketing plan.';
                return;
            }

            deletePlanModal.hide();
            showToast(data.message || 'Marketing plan deleted successfully.', 'success');

            setTimeout(() => {
                window.location.reload();
            }, 700);
        } catch (error) {
            alertEl.classList.remove('d-none');
            alertEl.textContent = 'Network error. Please try again.';
        } finally {
            setButtonLoading('deleteSubmitBtn', false);
        }
    }

    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toastId = `toast-${Date.now()}`;

        const bgClass = type === 'success' ? 'text-bg-success' : 'text-bg-danger';
        const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill';

        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${icon} me-2"></i>${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', toastHtml);

        const toastEl = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });

        toast.show();

        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    }
</script>
@endpush