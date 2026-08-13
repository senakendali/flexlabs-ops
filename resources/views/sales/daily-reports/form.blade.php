@extends('layouts.app-dashboard')

@section('title', $isEdit ? 'Edit Sales Daily Report' : 'Create Sales Daily Report')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Sales Reporting</div>
                <h1 class="page-title mb-2">{{ $isEdit ? 'Edit Sales Daily Report' : 'Create Sales Daily Report' }}</h1>
                <p class="page-subtitle mb-0">
                    Input laporan harian sales secara terstruktur agar lebih mudah dipantau, dibandingkan, dan dianalisa oleh management.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('sales-daily-reports.index') }}" class="btn btn-light btn-modern">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>
            </div>
        </div>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <form
        id="salesDailyReportForm"
        action="{{ $isEdit ? route('sales-daily-reports.update', $report) : route('sales-daily-reports.store') }}"
        method="POST"
        data-redirect="{{ route('sales-daily-reports.index') }}"
        data-kommo-summary-url="{{ route('sales-daily-reports.kommo-summary') }}"
        data-payment-summary-url="{{ route('sales-daily-reports.payment-summary') }}"
    >
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div id="formAlert" class="alert alert-danger d-none mb-4"></div>

        <div class="content-card mb-4">
            <div class="content-card-header">
                <div>
                    <h5 class="content-card-title mb-1">Report Information</h5>
                    <p class="content-card-subtitle mb-0">
                        Tentukan tanggal laporan dan gunakan angka yang sesuai dengan report sales pada hari tersebut.
                    </p>
                </div>
            </div>

            <div class="content-card-body">
                <div class="row g-3">
                    <div class="col-xl-4 col-md-6">
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
            </div>
        </div>

        <div class="content-card mb-4">
            <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <h5 class="content-card-title mb-1">Lead Metrics</h5>
                    <p class="content-card-subtitle mb-0">
                        Data leads akan otomatis ditarik dari Kommo setelah tanggal laporan dipilih atau berubah.
                    </p>
                </div>

                <div
                    id="kommoSummaryStatus"
                    class="d-inline-flex align-items-center gap-2 rounded-pill bg-light border px-3 py-2 small text-muted"
                >
                    <span
                        id="kommoSummarySpinner"
                        class="spinner-border spinner-border-sm d-none"
                        role="status"
                        aria-hidden="true"
                    ></span>
                    <i id="kommoSummaryIcon" class="bi bi-cloud-arrow-down"></i>
                    <span id="kommoSummaryText">Menunggu tanggal laporan.</span>
                </div>
            </div>

            <div class="content-card-body">
                <div class="row g-3">
                    <div class="col-xl-3 col-md-6">
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

                    <div class="col-xl-3 col-md-6">
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

                    <div class="col-xl-3 col-md-6">
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

                    <div class="col-xl-3 col-md-6">
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

                    <div class="col-xl-3 col-md-6">
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

                    <div class="col-xl-3 col-md-6">
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

                    <div class="col-xl-3 col-md-6">
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

                    <div class="col-xl-3 col-md-6">
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
            </div>
        </div>

        <div class="content-card mb-4">
            <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <h5 class="content-card-title mb-1">Sales Outcome</h5>
                    <p class="content-card-subtitle mb-0">
                        Hasil akhir dari aktivitas sales hari ini. Bagian ini membantu management melihat performa nyata dalam bentuk deal dan revenue.
                    </p>
                </div>

                <div
                    id="paymentSummaryStatus"
                    class="d-inline-flex align-items-center gap-2 rounded-pill bg-light border px-3 py-2 small text-muted"
                >
                    <span
                        id="paymentSummarySpinner"
                        class="spinner-border spinner-border-sm d-none"
                        role="status"
                        aria-hidden="true"
                    ></span>
                    <i id="paymentSummaryIcon" class="bi bi-cash-coin"></i>
                    <span id="paymentSummaryText">Menunggu tanggal laporan.</span>
                </div>
            </div>

            <div class="content-card-body">
                <div class="row g-3">
                    <div class="col-xl-3 col-md-6">
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

                    <div class="col-xl-3 col-md-6">
                        <label for="revenue" class="form-label">Revenue (Rp) <span class="text-danger">*</span></label>
                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            id="revenue"
                            name="revenue"
                            class="form-control @error('revenue') is-invalid @enderror"
                            value="{{ old('revenue', $report->revenue ?? 0) }}"
                            readonly
                        >
                        <div class="form-text">Otomatis dari total payment berstatus paid pada tanggal laporan.</div>
                        <div class="invalid-feedback" id="error_revenue">
                            @error('revenue') {{ $message }} @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card mb-4">
            <div class="content-card-header">
                <div>
                    <h5 class="content-card-title mb-1">Narrative Insight</h5>
                    <p class="content-card-subtitle mb-0">
                        Tambahkan insight penting agar tidak hanya angka yang terlihat, tapi juga apa yang sebenarnya terjadi di lapangan hari ini.
                    </p>
                </div>
            </div>

            <div class="content-card-body">
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
            </div>
        </div>

        <div class="content-card">
            <div class="content-card-body">
                <div class="d-flex justify-content-end gap-2 flex-wrap">
                    <a href="{{ route('sales-daily-reports.index') }}" class="btn btn-outline-secondary btn-modern">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>

                    <button type="submit" class="btn btn-primary btn-modern" id="submitBtn">
                        <span class="default-text">
                            <i class="bi bi-check-circle me-2"></i>
                            {{ $isEdit ? 'Update Report' : 'Save Report' }}
                        </span>
                        <span class="loading-text d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const salesDailyReportForm = document.getElementById('salesDailyReportForm');
    const submitBtn = document.getElementById('submitBtn');
    const formAlert = document.getElementById('formAlert');
    const kommoSummaryUrl = salesDailyReportForm.dataset.kommoSummaryUrl;
    const paymentSummaryUrl = salesDailyReportForm.dataset.paymentSummaryUrl;
    const kommoSummaryStatus = document.getElementById('kommoSummaryStatus');
    const kommoSummarySpinner = document.getElementById('kommoSummarySpinner');
    const kommoSummaryIcon = document.getElementById('kommoSummaryIcon');
    const kommoSummaryText = document.getElementById('kommoSummaryText');
    const paymentSummaryStatus = document.getElementById('paymentSummaryStatus');
    const paymentSummarySpinner = document.getElementById('paymentSummarySpinner');
    const paymentSummaryIcon = document.getElementById('paymentSummaryIcon');
    const paymentSummaryText = document.getElementById('paymentSummaryText');

    let kommoSummaryAbortController = null;
    let kommoSummaryDebounceTimer = null;
    let paymentSummaryAbortController = null;
    let paymentSummaryDebounceTimer = null;

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

    function setKommoSummaryStatus(type, message, isLoading = false) {
        if (!kommoSummaryStatus || !kommoSummaryText) {
            return;
        }

        const statusClasses = {
            idle: 'd-inline-flex align-items-center gap-2 rounded-pill bg-light border px-3 py-2 small text-muted',
            loading: 'd-inline-flex align-items-center gap-2 rounded-pill bg-info-subtle border border-info-subtle px-3 py-2 small text-info-emphasis',
            success: 'd-inline-flex align-items-center gap-2 rounded-pill bg-success-subtle border border-success-subtle px-3 py-2 small text-success-emphasis',
            warning: 'd-inline-flex align-items-center gap-2 rounded-pill bg-warning-subtle border border-warning-subtle px-3 py-2 small text-warning-emphasis',
            danger: 'd-inline-flex align-items-center gap-2 rounded-pill bg-danger-subtle border border-danger-subtle px-3 py-2 small text-danger-emphasis',
        };

        const iconClasses = {
            idle: 'bi bi-cloud-arrow-down',
            loading: 'bi bi-cloud-arrow-down',
            success: 'bi bi-check-circle',
            warning: 'bi bi-exclamation-triangle',
            danger: 'bi bi-x-circle',
        };

        kommoSummaryStatus.className = statusClasses[type] || statusClasses.idle;
        kommoSummaryText.textContent = message;

        if (kommoSummarySpinner) {
            kommoSummarySpinner.classList.toggle('d-none', !isLoading);
        }

        if (kommoSummaryIcon) {
            kommoSummaryIcon.className = iconClasses[type] || iconClasses.idle;
            kommoSummaryIcon.classList.toggle('d-none', isLoading);
        }
    }

    function setPaymentSummaryStatus(type, message, isLoading = false) {
        if (!paymentSummaryStatus || !paymentSummaryText) {
            return;
        }

        const statusClasses = {
            idle: 'd-inline-flex align-items-center gap-2 rounded-pill bg-light border px-3 py-2 small text-muted',
            loading: 'd-inline-flex align-items-center gap-2 rounded-pill bg-info-subtle border border-info-subtle px-3 py-2 small text-info-emphasis',
            success: 'd-inline-flex align-items-center gap-2 rounded-pill bg-success-subtle border border-success-subtle px-3 py-2 small text-success-emphasis',
            warning: 'd-inline-flex align-items-center gap-2 rounded-pill bg-warning-subtle border border-warning-subtle px-3 py-2 small text-warning-emphasis',
            danger: 'd-inline-flex align-items-center gap-2 rounded-pill bg-danger-subtle border border-danger-subtle px-3 py-2 small text-danger-emphasis',
        };

        const iconClasses = {
            idle: 'bi bi-cash-coin',
            loading: 'bi bi-cash-coin',
            success: 'bi bi-check-circle',
            warning: 'bi bi-exclamation-triangle',
            danger: 'bi bi-x-circle',
        };

        paymentSummaryStatus.className = statusClasses[type] || statusClasses.idle;
        paymentSummaryText.textContent = message;

        if (paymentSummarySpinner) {
            paymentSummarySpinner.classList.toggle('d-none', !isLoading);
        }

        if (paymentSummaryIcon) {
            paymentSummaryIcon.className = iconClasses[type] || iconClasses.idle;
            paymentSummaryIcon.classList.toggle('d-none', isLoading);
        }
    }

    function setKommoMetricFields(data = {}) {
        const metricKeys = [
            'total_leads',
            'interacted',
            'ignored',
            'closed_lost',
            'not_related',
            'warm_leads',
            'hot_leads',
            'consultation',
        ];

        metricKeys.forEach(key => {
            if (!fields[key]) {
                return;
            }

            const value = Number(data[key] ?? 0);

            fields[key].value = Number.isFinite(value) && value >= 0 ? value : 0;
            fields[key].classList.remove('is-invalid');
        });
    }

    async function fetchKommoSummary(reportDate, options = {}) {
        if (!reportDate) {
            setKommoSummaryStatus('idle', 'Menunggu tanggal laporan.');
            return;
        }

        if (!kommoSummaryUrl) {
            setKommoSummaryStatus('warning', 'Endpoint Kommo summary belum tersedia.');
            return;
        }

        if (kommoSummaryAbortController) {
            kommoSummaryAbortController.abort();
        }

        kommoSummaryAbortController = new AbortController();

        setKommoSummaryStatus('loading', 'Menarik data Kommo...', true);

        try {
            const url = new URL(kommoSummaryUrl, window.location.origin);
            url.searchParams.set('date', reportDate);

            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: kommoSummaryAbortController.signal,
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Gagal menarik data Kommo.');
            }

            setKommoMetricFields(result.data || {});
            setKommoSummaryStatus('success', `Data Kommo ${reportDate} berhasil ditarik.`);

            if (options.showToast) {
                showToast(result.message || 'Data Kommo berhasil ditarik.', 'success');
            }
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            setKommoSummaryStatus('danger', error.message || 'Gagal menarik data Kommo.');

            if (options.showToast) {
                showToast(error.message || 'Gagal menarik data Kommo.', 'danger');
            }
        }
    }

    function scheduleKommoSummaryFetch(reportDate, options = {}) {
        clearTimeout(kommoSummaryDebounceTimer);

        kommoSummaryDebounceTimer = setTimeout(() => {
            fetchKommoSummary(reportDate, options);
        }, 350);
    }

    async function fetchPaymentSummary(reportDate, options = {}) {
        if (!reportDate) {
            setPaymentSummaryStatus('idle', 'Menunggu tanggal laporan.');
            return;
        }

        if (!paymentSummaryUrl) {
            setPaymentSummaryStatus('warning', 'Endpoint payment summary belum tersedia.');
            return;
        }

        if (paymentSummaryAbortController) {
            paymentSummaryAbortController.abort();
        }

        paymentSummaryAbortController = new AbortController();
        setPaymentSummaryStatus('loading', 'Menghitung revenue...', true);

        try {
            const url = new URL(paymentSummaryUrl, window.location.origin);
            url.searchParams.set('date', reportDate);

            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: paymentSummaryAbortController.signal,
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Gagal menghitung revenue dari payment.');
            }

            const revenue = Number(result.data?.revenue ?? 0);
            fields.revenue.value = Number.isFinite(revenue) && revenue >= 0 ? revenue : 0;
            fields.revenue.classList.remove('is-invalid');
            setPaymentSummaryStatus('success', `Revenue ${reportDate} berhasil dihitung.`);

            if (options.showToast) {
                showToast(result.message || 'Revenue dari payment berhasil diperbarui.', 'success');
            }
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            setPaymentSummaryStatus('danger', error.message || 'Gagal menghitung revenue dari payment.');

            if (options.showToast) {
                showToast(error.message || 'Gagal menghitung revenue dari payment.', 'danger');
            }
        }
    }

    function schedulePaymentSummaryFetch(reportDate, options = {}) {
        clearTimeout(paymentSummaryDebounceTimer);

        paymentSummaryDebounceTimer = setTimeout(() => {
            fetchPaymentSummary(reportDate, options);
        }, 350);
    }

    if (fields.report_date) {
        fields.report_date.addEventListener('change', function () {
            scheduleKommoSummaryFetch(this.value, { showToast: true });
            schedulePaymentSummaryFetch(this.value, { showToast: true });
        });

        if (fields.report_date.value) {
            scheduleKommoSummaryFetch(fields.report_date.value);
            schedulePaymentSummaryFetch(fields.report_date.value);
        }
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