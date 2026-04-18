@extends('layouts.app-dashboard')

@section('title', 'Marketing Report Detail')

@section('content')
<div class="container-fluid px-4 py-4">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Management Reporting</div>
                <h1 class="page-title mb-2">{{ $marketingReport->title }}</h1>
                <p class="page-subtitle mb-0">
                    Kelola detail laporan marketing pada area kerja yang lebih luas agar pengisian data,
                    insight, dan evaluasi lebih nyaman dibandingkan popup.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('marketing.reports.index') }}" class="btn btn-light border btn-modern">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
                <button type="button" class="btn btn-danger btn-modern" onclick="deleteCurrentReport()">
                    <i class="bi bi-trash me-1"></i> Delete
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

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Leads</div>
                        <div class="stat-value">{{ number_format($marketingReport->total_leads) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    {{ number_format($marketingReport->qualified_leads) }} qualified
                    · {{ number_format($marketingReport->qualified_rate, 2) }}%
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Conversions</div>
                        <div class="stat-value">{{ number_format($marketingReport->total_conversions) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Conversion rate {{ number_format($marketingReport->conversion_rate, 2) }}%
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div>
                        <div class="stat-title">Revenue</div>
                        <div class="stat-value">Rp{{ number_format((float) $marketingReport->total_revenue, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Total revenue pada periode report ini
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <div class="stat-title">Actual Spend</div>
                        <div class="stat-value">Rp{{ number_format((float) $marketingReport->actual_spend, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    CPL Rp{{ number_format($marketingReport->cpl, 0, ',', '.') }}
                    · CAC Rp{{ number_format($marketingReport->cac, 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    <form id="reportEditForm">
        @csrf
        <input type="hidden" name="_method" value="PUT">

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="content-card h-100">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Report Detail</h5>
                            <p class="content-card-subtitle mb-0">
                                Perbarui detail laporan marketing, metrik utama, dan narasi yang dibutuhkan management.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="title" class="form-label">
                                    Title <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="title" name="title" class="form-control" value="{{ $marketingReport->title }}">
                                <div class="invalid-feedback" id="error_title"></div>
                            </div>

                            <div class="col-md-4">
                                <label for="period_type" class="form-label">
                                    Period Type <span class="text-danger">*</span>
                                </label>
                                <select id="period_type" name="period_type" class="form-select">
                                    <option value="weekly" {{ $marketingReport->period_type === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                    <option value="monthly" {{ $marketingReport->period_type === 'monthly' ? 'selected' : '' }}>Monthly</option>
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
                                    value="{{ optional($marketingReport->start_date)->format('Y-m-d') }}"
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
                                    value="{{ optional($marketingReport->end_date)->format('Y-m-d') }}"
                                >
                                <div class="invalid-feedback" id="error_end_date"></div>
                            </div>

                            <div class="col-md-4">
                                <label for="total_leads" class="form-label">Total Leads</label>
                                <input type="number" id="total_leads" name="total_leads" class="form-control" min="0" step="1" value="{{ $marketingReport->total_leads }}">
                                <div class="invalid-feedback" id="error_total_leads"></div>
                            </div>

                            <div class="col-md-4">
                                <label for="qualified_leads" class="form-label">Qualified Leads</label>
                                <input type="number" id="qualified_leads" name="qualified_leads" class="form-control" min="0" step="1" value="{{ $marketingReport->qualified_leads }}">
                                <div class="invalid-feedback" id="error_qualified_leads"></div>
                            </div>

                            <div class="col-md-4">
                                <label for="total_conversions" class="form-label">Total Conversions</label>
                                <input type="number" id="total_conversions" name="total_conversions" class="form-control" min="0" step="1" value="{{ $marketingReport->total_conversions }}">
                                <div class="invalid-feedback" id="error_total_conversions"></div>
                            </div>

                            <div class="col-md-4">
                                <label for="total_revenue" class="form-label">Total Revenue</label>
                                <input type="number" id="total_revenue" name="total_revenue" class="form-control" min="0" step="0.01" value="{{ (float) $marketingReport->total_revenue }}">
                                <div class="invalid-feedback" id="error_total_revenue"></div>
                            </div>

                            <div class="col-md-4">
                                <label for="budget" class="form-label">Budget</label>
                                <input type="number" id="budget" name="budget" class="form-control" min="0" step="0.01" value="{{ (float) $marketingReport->budget }}">
                                <div class="invalid-feedback" id="error_budget"></div>
                            </div>

                            <div class="col-md-4">
                                <label for="actual_spend" class="form-label">Actual Spend</label>
                                <input type="number" id="actual_spend" name="actual_spend" class="form-control" min="0" step="0.01" value="{{ (float) $marketingReport->actual_spend }}">
                                <div class="invalid-feedback" id="error_actual_spend"></div>
                            </div>

                            <div class="col-12">
                                <label for="summary" class="form-label">Summary</label>
                                <textarea id="summary" name="summary" rows="4" class="form-control">{{ $marketingReport->summary }}</textarea>
                                <div class="invalid-feedback" id="error_summary"></div>
                            </div>

                            <div class="col-12">
                                <label for="key_insight" class="form-label">Key Insight</label>
                                <textarea id="key_insight" name="key_insight" rows="4" class="form-control">{{ $marketingReport->key_insight }}</textarea>
                                <div class="invalid-feedback" id="error_key_insight"></div>
                            </div>

                            <div class="col-12">
                                <label for="next_action" class="form-label">Next Action</label>
                                <textarea id="next_action" name="next_action" rows="4" class="form-control">{{ $marketingReport->next_action }}</textarea>
                                <div class="invalid-feedback" id="error_next_action"></div>
                            </div>

                            <div class="col-12">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea id="notes" name="notes" rows="3" class="form-control">{{ $marketingReport->notes }}</textarea>
                                <div class="invalid-feedback" id="error_notes"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="content-card mb-3">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Publish Setting</h5>
                            <p class="content-card-subtitle mb-0">
                                Atur status report dan visibilitas data.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select id="status" name="status" class="form-select">
                                <option value="draft" {{ $marketingReport->status === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="published" {{ $marketingReport->status === 'published' ? 'selected' : '' }}>Published</option>
                                <option value="archived" {{ $marketingReport->status === 'archived' ? 'selected' : '' }}>Archived</option>
                            </select>
                            <div class="invalid-feedback" id="error_status"></div>
                        </div>

                        <div class="form-check mb-3">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="is_active"
                                name="is_active"
                                value="1"
                                {{ $marketingReport->is_active ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                        <div class="invalid-feedback d-block" id="error_is_active"></div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-modern" id="submitBtn">
                                <span class="default-text">
                                    <i class="bi bi-floppy me-1"></i> Save Changes
                                </span>
                                <span class="loading-text d-none">Saving...</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Audit Info</h5>
                            <p class="content-card-subtitle mb-0">
                                Informasi pencatatan dan pembaruan report.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="audit-item">
                            <div class="audit-label">Created By</div>
                            <div class="audit-value">{{ optional($marketingReport->creator)->name ?? '-' }}</div>
                        </div>

                        <div class="audit-item">
                            <div class="audit-label">Updated By</div>
                            <div class="audit-value">{{ optional($marketingReport->updater)->name ?? '-' }}</div>
                        </div>

                        <div class="audit-item">
                            <div class="audit-label">Created At</div>
                            <div class="audit-value">{{ optional($marketingReport->created_at)->format('d M Y H:i') ?? '-' }}</div>
                        </div>

                        <div class="audit-item mb-0">
                            <div class="audit-label">Updated At</div>
                            <div class="audit-value">{{ optional($marketingReport->updated_at)->format('d M Y H:i') ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>
@endsection

@push('styles')
<style>
    
</style>
@endpush

@push('scripts')
<script>
    const reportUpdateUrl = @json(route('marketing.reports.update', $marketingReport));
    const reportDeleteUrl = @json(route('marketing.reports.destroy', $marketingReport));
    const reportIndexUrl = @json(route('marketing.reports.index'));
    const csrfToken = @json(csrf_token());

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('reportEditForm')?.addEventListener('submit', submitReportUpdate);
    });

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

    async function submitReportUpdate(event) {
        event.preventDefault();

        const form = document.getElementById('reportEditForm');
        const alertEl = document.getElementById('formAlert');

        clearValidationErrors(form);
        alertEl.classList.add('d-none');
        alertEl.textContent = '';

        const formData = new FormData(form);

        if (!document.getElementById('is_active').checked) {
            formData.delete('is_active');
        }

        setButtonLoading('submitBtn', true);

        try {
            const response = await fetch(reportUpdateUrl, {
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
                    alertEl.classList.remove('d-none');
                    alertEl.textContent = data.message || 'Failed to update marketing report.';
                }

                return;
            }

            showToast(data.message || 'Marketing report updated successfully.', 'success');
        } catch (error) {
            alertEl.classList.remove('d-none');
            alertEl.textContent = 'Network error. Please try again.';
        } finally {
            setButtonLoading('submitBtn', false);
        }
    }

    async function deleteCurrentReport() {
        const confirmed = window.confirm('Delete this marketing report?');
        if (!confirmed) return;

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('_method', 'DELETE');

        try {
            const response = await fetch(reportDeleteUrl, {
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
                showToast(data.message || 'Failed to delete marketing report.', 'danger');
                return;
            }

            showToast(data.message || 'Marketing report deleted successfully.', 'success');

            setTimeout(() => {
                window.location.href = reportIndexUrl;
            }, 500);
        } catch (error) {
            showToast('Network error. Please try again.', 'danger');
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