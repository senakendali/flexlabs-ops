@extends('layouts.app-dashboard')

@section('title', 'Marketing Reports')

@section('content')
<div class="container-fluid px-4 py-4">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Management Reporting</div>
                <h1 class="page-title mb-2">Marketing Reports</h1>
                <p class="page-subtitle mb-0">
                    Kelola laporan marketing mingguan atau bulanan yang menjadi sumber data utama
                    untuk dashboard management.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-primary btn-modern" onclick="openCreateModal()">
                    <i class="bi bi-plus-circle me-1"></i> Add Report
                </button>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;"></div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filter --}}
    <div class="content-card mb-4">
        <div class="content-card-body">
            <form method="GET" action="{{ route('marketing.reports.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input
                        type="text"
                        name="search"
                        class="form-control form-control-modern"
                        value="{{ $search ?? '' }}"
                        placeholder="Search title, summary, insight..."
                    >
                </div>

                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-control-modern">
                        <option value="">All Status</option>
                        @foreach (['draft', 'published', 'archived'] as $item)
                            <option value="{{ $item }}" {{ ($status ?? '') === $item ? 'selected' : '' }}>
                                {{ ucfirst($item) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Period Type</label>
                    <select name="period_type" class="form-select form-control-modern">
                        <option value="">All Period</option>
                        @foreach (['weekly', 'monthly'] as $item)
                            <option value="{{ $item }}" {{ ($periodType ?? '') === $item ? 'selected' : '' }}>
                                {{ ucfirst($item) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Per Page</label>
                    <select name="per_page" class="form-select form-control-modern">
                        @foreach ([10, 25, 50, 100] as $item)
                            <option value="{{ $item }}" {{ (int) ($perPage ?? 10) === $item ? 'selected' : '' }}>
                                {{ $item }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-modern w-100">
                        <i class="bi bi-funnel-fill me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- List --}}
    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Report List</h5>
                <p class="content-card-subtitle mb-0">
                    Daftar laporan marketing yang sudah dibuat dan siap dibaca management.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">#</th>
                            <th>Title</th>
                            <th>Period</th>
                            <th class="text-end">Leads</th>
                            <th class="text-end">Conversions</th>
                            <th class="text-end">Revenue</th>
                            <th>Status</th>
                            <th>Active</th>
                            <th class="text-end" style="width: 190px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reports as $index => $report)
                            @php
                                $statusClass = match ($report->status) {
                                    'draft' => 'bg-secondary-subtle text-secondary-emphasis',
                                    'published' => 'bg-success-subtle text-success-emphasis',
                                    'archived' => 'bg-warning-subtle text-warning-emphasis',
                                    default => 'bg-light text-dark',
                                };
                            @endphp
                            <tr>
                                <td>{{ $reports->firstItem() + $index }}</td>

                                <td>
                                    <div class="fw-semibold">{{ $report->title }}</div>
                                    <div class="small text-muted">
                                        {{ \Illuminate\Support\Str::limit($report->summary, 80) ?: '-' }}
                                    </div>
                                </td>

                                <td>
                                    <div class="fw-medium text-capitalize">{{ $report->period_type }}</div>
                                    <div class="small text-muted">{{ $report->period_label }}</div>
                                </td>

                                <td class="text-end">{{ number_format($report->total_leads) }}</td>
                                <td class="text-end">{{ number_format($report->total_conversions) }}</td>
                                <td class="text-end">Rp{{ number_format((float) $report->total_revenue, 0, ',', '.') }}</td>

                                <td>
                                    <span class="badge rounded-pill {{ $statusClass }}">
                                        {{ ucfirst($report->status) }}
                                    </span>
                                </td>

                                <td>
                                    @if ($report->is_active)
                                        <span class="badge rounded-pill bg-success-subtle text-success-emphasis">Yes</span>
                                    @else
                                        <span class="badge rounded-pill bg-light text-dark">No</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a
                                            href="{{ route('marketing.reports.show', $report) }}"
                                            class="btn btn-sm btn-light border"
                                            title="Open Report"
                                        >
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-light border text-danger"
                                            title="Delete Report"
                                            data-id="{{ $report->id }}"
                                            data-title="{{ e($report->title) }}"
                                            onclick="openDeleteModal(this)"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    No marketing report found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($reports->hasPages())
                <div class="mt-3">
                    {{ $reports->links() }}
                </div>
            @endif
        </div>
    </div>

</div>

{{-- Create Modal --}}
<div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form id="reportForm">
            @csrf
            <input type="hidden" id="report_id" name="report_id">
            <input type="hidden" id="form_method" name="_method" value="POST">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportModalTitle">Add Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="title" class="form-label">
                                Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="title" name="title" class="form-control">
                            <div class="invalid-feedback" id="error_title"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="period_type" class="form-label">
                                Period Type <span class="text-danger">*</span>
                            </label>
                            <select id="period_type" name="period_type" class="form-select">
                                <option value="">Select period type</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                            <div class="invalid-feedback" id="error_period_type"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" id="start_date" name="start_date" class="form-control">
                            <div class="invalid-feedback" id="error_start_date"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" id="end_date" name="end_date" class="form-control">
                            <div class="invalid-feedback" id="error_end_date"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="total_leads" class="form-label">Total Leads</label>
                            <input type="number" id="total_leads" name="total_leads" class="form-control" min="0" step="1">
                            <div class="invalid-feedback" id="error_total_leads"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="qualified_leads" class="form-label">Qualified Leads</label>
                            <input type="number" id="qualified_leads" name="qualified_leads" class="form-control" min="0" step="1">
                            <div class="invalid-feedback" id="error_qualified_leads"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="total_conversions" class="form-label">Total Conversions</label>
                            <input type="number" id="total_conversions" name="total_conversions" class="form-control" min="0" step="1">
                            <div class="invalid-feedback" id="error_total_conversions"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="total_revenue" class="form-label">Total Revenue</label>
                            <input type="number" id="total_revenue" name="total_revenue" class="form-control" min="0" step="0.01">
                            <div class="invalid-feedback" id="error_total_revenue"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="budget" class="form-label">Budget</label>
                            <input type="number" id="budget" name="budget" class="form-control" min="0" step="0.01">
                            <div class="invalid-feedback" id="error_budget"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="actual_spend" class="form-label">Actual Spend</label>
                            <input type="number" id="actual_spend" name="actual_spend" class="form-control" min="0" step="0.01">
                            <div class="invalid-feedback" id="error_actual_spend"></div>
                        </div>

                        <div class="col-12">
                            <label for="summary" class="form-label">Summary</label>
                            <textarea id="summary" name="summary" rows="3" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_summary"></div>
                        </div>

                        <div class="col-12">
                            <label for="key_insight" class="form-label">Key Insight</label>
                            <textarea id="key_insight" name="key_insight" rows="3" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_key_insight"></div>
                        </div>

                        <div class="col-12">
                            <label for="next_action" class="form-label">Next Action</label>
                            <textarea id="next_action" name="next_action" rows="3" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_next_action"></div>
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" name="notes" rows="3" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_notes"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select id="status" name="status" class="form-select">
                                <option value="">Select status</option>
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                            <div class="invalid-feedback" id="error_status"></div>
                        </div>

                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
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
<div class="modal fade" id="deleteReportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="deleteReportForm">
            @csrf
            <input type="hidden" name="_method" value="DELETE">
            <input type="hidden" id="delete_report_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div id="deleteAlert" class="alert alert-danger d-none mb-3"></div>
                    <p class="mb-0">
                        Are you sure you want to delete
                        <strong id="delete_report_title">this report</strong>?
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

@push('styles')
<style>
    .form-control-modern {
        min-width: 160px;
        border-radius: 12px;
        border: 1px solid rgba(91, 62, 142, 0.14);
        box-shadow: none;
    }

    .btn-modern {
        border-radius: 12px;
        padding-inline: 16px;
        font-weight: 600;
    }

    .table-modern thead th {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #6b7280;
        background: #f8fafc;
        border-bottom: 1px solid #eef2f7;
        padding-top: 14px;
        padding-bottom: 14px;
    }

    .table-modern tbody td {
        padding-top: 14px;
        padding-bottom: 14px;
        border-color: #eef2f7;
        vertical-align: middle;
    }

    .table-modern tbody tr:hover {
        background: rgba(91, 62, 142, 0.025);
    }
</style>
@endpush

@push('scripts')
<script>
    const reportStoreUrl = @json(route('marketing.reports.store'));
    const reportDeleteUrlTemplate = @json(route('marketing.reports.destroy', ['marketingReport' => '__ID__']));
    const csrfToken = @json(csrf_token());

    let reportModal;
    let deleteReportModal;

    document.addEventListener('DOMContentLoaded', function () {
        const reportModalEl = document.getElementById('reportModal');
        const deleteReportModalEl = document.getElementById('deleteReportModal');

        if (reportModalEl) {
            reportModal = new bootstrap.Modal(reportModalEl);
        }

        if (deleteReportModalEl) {
            deleteReportModal = new bootstrap.Modal(deleteReportModalEl);
        }

        document.getElementById('reportForm')?.addEventListener('submit', submitReportForm);
        document.getElementById('deleteReportForm')?.addEventListener('submit', submitDeleteForm);
    });

    function openCreateModal() {
        resetReportForm();
        document.getElementById('reportModalTitle').textContent = 'Add Report';
        document.getElementById('form_method').value = 'POST';
        document.getElementById('is_active').checked = true;
        reportModal.show();
    }

    function openDeleteModal(button) {
        resetDeleteForm();
        document.getElementById('delete_report_id').value = button.dataset.id || '';
        document.getElementById('delete_report_title').textContent = button.dataset.title || 'this report';
        deleteReportModal.show();
    }

    function resetReportForm() {
        const form = document.getElementById('reportForm');
        if (!form) return;

        form.reset();
        document.getElementById('report_id').value = '';
        document.getElementById('form_method').value = 'POST';
        document.getElementById('formAlert').classList.add('d-none');
        document.getElementById('formAlert').textContent = '';

        clearValidationErrors(form);
    }

    function resetDeleteForm() {
        document.getElementById('delete_report_id').value = '';
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
            'total_leads',
            'qualified_leads',
            'total_conversions',
            'total_revenue',
            'budget',
            'actual_spend',
            'summary',
            'key_insight',
            'next_action',
            'notes',
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

    async function parseJsonSafe(response) {
        const text = await response.text();

        try {
            return text ? JSON.parse(text) : {};
        } catch (e) {
            return {
                success: false,
                message: text || 'Invalid server response.',
            };
        }
    }

    async function submitReportForm(event) {
        event.preventDefault();

        const form = document.getElementById('reportForm');

        clearValidationErrors(form);
        document.getElementById('formAlert').classList.add('d-none');
        document.getElementById('formAlert').textContent = '';

        const formData = new FormData(form);

        if (!document.getElementById('is_active').checked) {
            formData.delete('is_active');
        }

        setButtonLoading('submitBtn', true);

        try {
            const response = await fetch(reportStoreUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await parseJsonSafe(response);

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
                    alertEl.textContent = data.message || 'Failed to save marketing report.';
                }

                return;
            }

            reportModal.hide();
            showToast(data.message || 'Marketing report saved successfully.', 'success');

            setTimeout(() => {
                window.location.reload();
            }, 500);
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

        const reportId = document.getElementById('delete_report_id').value;
        const alertEl = document.getElementById('deleteAlert');

        alertEl.classList.add('d-none');
        alertEl.textContent = '';

        if (!reportId) {
            alertEl.classList.remove('d-none');
            alertEl.textContent = 'Report ID not found.';
            return;
        }

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('_method', 'DELETE');

        setButtonLoading('deleteSubmitBtn', true);

        try {
            const response = await fetch(reportDeleteUrlTemplate.replace('__ID__', reportId), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await parseJsonSafe(response);

            if (!response.ok) {
                alertEl.classList.remove('d-none');
                alertEl.textContent = data.message || 'Failed to delete marketing report.';
                return;
            }

            deleteReportModal.hide();
            showToast(data.message || 'Marketing report deleted successfully.', 'success');

            setTimeout(() => {
                window.location.reload();
            }, 500);
        } catch (error) {
            alertEl.classList.remove('d-none');
            alertEl.textContent = 'Network error. Please try again.';
        } finally {
            setButtonLoading('deleteSubmitBtn', false);
        }
    }

    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) {
            alert(message);
            return;
        }

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