@extends('layouts.app-dashboard')

@section('title', $isEdit ? 'Edit Sales Daily Report' : 'Create Sales Daily Report')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">{{ $isEdit ? 'Edit Sales Daily Report' : 'Create Sales Daily Report' }}</h4>
            <small class="text-muted">
                Input laporan harian sales secara terstruktur agar lebih mudah dipantau, dibandingkan, dan dianalisa oleh management.
            </small>
        </div>

        <a href="{{ route('sales-daily-reports.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form
                id="salesDailyReportForm"
                action="{{ $isEdit ? route('sales-daily-reports.update', $report) : route('sales-daily-reports.store') }}"
                method="POST"
                data-redirect="{{ route('sales-daily-reports.index') }}"
            >
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div id="formAlert" class="alert alert-danger d-none mb-4"></div>

                <div class="mb-4">
                    <h5 class="fw-bold mb-1">Report Information</h5>
                    <p class="text-muted small mb-0">
                        Tentukan tanggal laporan dan gunakan angka yang sesuai dengan report sales pada hari tersebut.
                    </p>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="report_date" class="form-label">
                            Report Date <span class="text-danger">*</span>
                        </label>
                        <input
                            type="date"
                            id="report_date"
                            name="report_date"
                            class="form-control @error('report_date') is-invalid @enderror"
                            value="{{ old('report_date', optional($report->report_date)->format('Y-m-d') ?? $report->report_date) }}"
                        >
                        <div class="invalid-feedback" id="error_report_date">
                            @error('report_date') {{ $message }} @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="fw-bold mb-1">Lead Metrics</h5>
                    <p class="text-muted small mb-0">
                        Masukkan angka utama dari daily report untuk membantu membaca volume leads, kualitas interaksi, dan peluang konsultasi.
                    </p>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="total_leads" class="form-label">Total Leads <span class="text-danger">*</span></label>
                        <input
                            type="number"
                            min="0"
                            id="total_leads"
                            name="total_leads"
                            class="form-control @error('total_leads') is-invalid @enderror"
                            value="{{ old('total_leads', $report->total_leads ?? 0) }}"
                        >
                        <div class="invalid-feedback" id="error_total_leads">
                            @error('total_leads') {{ $message }} @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="interacted" class="form-label">Interacted <span class="text-danger">*</span></label>
                        <input
                            type="number"
                            min="0"
                            id="interacted"
                            name="interacted"
                            class="form-control @error('interacted') is-invalid @enderror"
                            value="{{ old('interacted', $report->interacted ?? 0) }}"
                        >
                        <div class="invalid-feedback" id="error_interacted">
                            @error('interacted') {{ $message }} @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="ignored" class="form-label">Ignored <span class="text-danger">*</span></label>
                        <input
                            type="number"
                            min="0"
                            id="ignored"
                            name="ignored"
                            class="form-control @error('ignored') is-invalid @enderror"
                            value="{{ old('ignored', $report->ignored ?? 0) }}"
                        >
                        <div class="invalid-feedback" id="error_ignored">
                            @error('ignored') {{ $message }} @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="closed_lost" class="form-label">Closed Lost <span class="text-danger">*</span></label>
                        <input
                            type="number"
                            min="0"
                            id="closed_lost"
                            name="closed_lost"
                            class="form-control @error('closed_lost') is-invalid @enderror"
                            value="{{ old('closed_lost', $report->closed_lost ?? 0) }}"
                        >
                        <div class="invalid-feedback" id="error_closed_lost">
                            @error('closed_lost') {{ $message }} @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="not_related" class="form-label">Not Related <span class="text-danger">*</span></label>
                        <input
                            type="number"
                            min="0"
                            id="not_related"
                            name="not_related"
                            class="form-control @error('not_related') is-invalid @enderror"
                            value="{{ old('not_related', $report->not_related ?? 0) }}"
                        >
                        <div class="invalid-feedback" id="error_not_related">
                            @error('not_related') {{ $message }} @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="warm_leads" class="form-label">Warm Leads <span class="text-danger">*</span></label>
                        <input
                            type="number"
                            min="0"
                            id="warm_leads"
                            name="warm_leads"
                            class="form-control @error('warm_leads') is-invalid @enderror"
                            value="{{ old('warm_leads', $report->warm_leads ?? 0) }}"
                        >
                        <div class="invalid-feedback" id="error_warm_leads">
                            @error('warm_leads') {{ $message }} @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="hot_leads" class="form-label">Hot Leads <span class="text-danger">*</span></label>
                        <input
                            type="number"
                            min="0"
                            id="hot_leads"
                            name="hot_leads"
                            class="form-control @error('hot_leads') is-invalid @enderror"
                            value="{{ old('hot_leads', $report->hot_leads ?? 0) }}"
                        >
                        <div class="invalid-feedback" id="error_hot_leads">
                            @error('hot_leads') {{ $message }} @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="consultation" class="form-label">Consultation <span class="text-danger">*</span></label>
                        <input
                            type="number"
                            min="0"
                            id="consultation"
                            name="consultation"
                            class="form-control @error('consultation') is-invalid @enderror"
                            value="{{ old('consultation', $report->consultation ?? 0) }}"
                        >
                        <div class="invalid-feedback" id="error_consultation">
                            @error('consultation') {{ $message }} @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="fw-bold mb-1">Sales Outcome</h5>
                    <p class="text-muted small mb-0">
                        Hasil akhir dari aktivitas sales hari ini. Bagian ini membantu management melihat performa nyata dalam bentuk deal dan revenue.
                    </p>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="closed_deal" class="form-label">Closed Deal <span class="text-danger">*</span></label>
                        <input
                            type="number"
                            min="0"
                            id="closed_deal"
                            name="closed_deal"
                            class="form-control @error('closed_deal') is-invalid @enderror"
                            value="{{ old('closed_deal', $report->closed_deal ?? 0) }}"
                        >
                        <div class="invalid-feedback" id="error_closed_deal">
                            @error('closed_deal') {{ $message }} @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="revenue" class="form-label">Revenue (Rp) <span class="text-danger">*</span></label>
                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            id="revenue"
                            name="revenue"
                            class="form-control @error('revenue') is-invalid @enderror"
                            value="{{ old('revenue', $report->revenue ?? 0) }}"
                        >
                        <div class="invalid-feedback" id="error_revenue">
                            @error('revenue') {{ $message }} @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="fw-bold mb-1">Narrative Insight</h5>
                    <p class="text-muted small mb-0">
                        Tambahkan insight penting agar tidak hanya angka yang terlihat, tapi juga apa yang sebenarnya terjadi di lapangan hari ini.
                    </p>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label for="summary" class="form-label">Leads Summary for Today</label>
                        <textarea
                            id="summary"
                            name="summary"
                            rows="4"
                            class="form-control @error('summary') is-invalid @enderror"
                            placeholder="Contoh: High lead volume for today, with half of the leads moving into interaction stage and 4 leads proceeding to consultation."
                        >{{ old('summary', $report->summary) }}</textarea>
                        <div class="invalid-feedback" id="error_summary">
                            @error('summary') {{ $message }} @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="highlight" class="form-label">Hot Lead Highlight</label>
                        <textarea
                            id="highlight"
                            name="highlight"
                            rows="5"
                            class="form-control @error('highlight') is-invalid @enderror"
                            placeholder="Contoh: 1 hot lead for Core SE already joined a 15-minute consultation with the parent and showed strong interest in the online class option."
                        >{{ old('highlight', $report->highlight) }}</textarea>
                        <div class="invalid-feedback" id="error_highlight">
                            @error('highlight') {{ $message }} @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="4"
                            class="form-control @error('notes') is-invalid @enderror"
                            placeholder="Tambahkan catatan tambahan jika ada hambatan, insight, atau next action penting."
                        >{{ old('notes', $report->notes) }}</textarea>
                        <div class="invalid-feedback" id="error_notes">
                            @error('notes') {{ $message }} @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('sales-daily-reports.index') }}" class="btn btn-light border">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span class="default-text">
                            <i class="bi bi-check-circle me-1"></i>
                            {{ $isEdit ? 'Update Report' : 'Save Report' }}
                        </span>
                        <span class="loading-text d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const salesDailyReportForm = document.getElementById('salesDailyReportForm');
    const submitBtn = document.getElementById('submitBtn');
    const formAlert = document.getElementById('formAlert');

    const fields = {
        report_date: document.getElementById('report_date'),
        total_leads: document.getElementById('total_leads'),
        interacted: document.getElementById('interacted'),
        ignored: document.getElementById('ignored'),
        closed_lost: document.getElementById('closed_lost'),
        not_related: document.getElementById('not_related'),
        warm_leads: document.getElementById('warm_leads'),
        hot_leads: document.getElementById('hot_leads'),
        consultation: document.getElementById('consultation'),
        closed_deal: document.getElementById('closed_deal'),
        revenue: document.getElementById('revenue'),
        summary: document.getElementById('summary'),
        highlight: document.getElementById('highlight'),
        notes: document.getElementById('notes'),
    };

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

    function clearValidationErrors() {
        Object.values(fields).forEach(field => {
            if (field && field.classList) {
                field.classList.remove('is-invalid');
            }
        });

        Object.keys(fields).forEach(key => {
            const errorEl = document.getElementById(`error_${key}`);
            if (errorEl) {
                errorEl.textContent = '';
            }
        });

        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';
    }

    function setValidationErrors(errors = {}) {
        clearValidationErrors();

        Object.keys(errors).forEach(key => {
            const field = fields[key];
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

    salesDailyReportForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();
        setSubmitLoading(true);

        const payload = {
            report_date: fields.report_date.value,
            total_leads: fields.total_leads.value || 0,
            interacted: fields.interacted.value || 0,
            ignored: fields.ignored.value || 0,
            closed_lost: fields.closed_lost.value || 0,
            not_related: fields.not_related.value || 0,
            warm_leads: fields.warm_leads.value || 0,
            hot_leads: fields.hot_leads.value || 0,
            consultation: fields.consultation.value || 0,
            closed_deal: fields.closed_deal.value || 0,
            revenue: fields.revenue.value || 0,
            summary: fields.summary.value.trim(),
            highlight: fields.highlight.value.trim(),
            notes: fields.notes.value.trim(),
        };

        const method = @json($isEdit ? 'PUT' : 'POST');
        const url = salesDailyReportForm.getAttribute('action');

        try {
            const response = await fetch(url, {
                method: method,
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
                throw new Error(result.message || 'Failed to save report.');
            }

            showToast(result.message || 'Sales daily report saved successfully.', 'success');

            setTimeout(() => {
                window.location.href = salesDailyReportForm.dataset.redirect;
            }, 900);
        } catch (error) {
            if (error.message !== 'Validation failed.') {
                formAlert.classList.remove('d-none');
                formAlert.innerHTML = error.message || 'Something went wrong.';
            }
        } finally {
            setSubmitLoading(false);
        }
    });
</script>
@endpush