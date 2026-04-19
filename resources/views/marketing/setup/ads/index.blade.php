@extends('layouts.app-dashboard')

@section('title', 'Marketing Setup - Ads')

@section('content')
<div class="container-fluid px-4 py-4 marketing-setup-page">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Marketing Setup</div>
                <h1 class="page-title mb-2">Ads Master</h1>
                <p class="page-subtitle mb-0">
                    Data dasar ads yang aktif pada periode tertentu, lalu akan dibaca otomatis saat report dibuat.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-primary" id="openCreateAdModalBtn">
                    <i class="bi bi-plus-circle me-1"></i> Add Ad
                </button>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;"></div>

    <div class="content-card mb-4">
        <div class="content-card-body">
            <form method="GET" action="{{ route('marketing.setup.ads.index') }}" class="row g-3 align-items-end">
                <div class="col-xl-3 col-md-6">
                    <label class="form-label">Search</label>
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Ad name, platform, objective..."
                        value="{{ request('search') }}"
                    >
                </div>

                <div class="col-xl-2 col-md-6">
                    <label class="form-label">Campaign</label>
                    <select name="marketing_setup_campaign_id" class="form-select">
                        <option value="">All Campaigns</option>
                        @foreach ($campaignOptions as $campaign)
                            <option value="{{ $campaign->id }}" @selected((string) request('marketing_setup_campaign_id') === (string) $campaign->id)>
                                {{ $campaign->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-2 col-md-6">
                    <label class="form-label">Platform</label>
                    <select name="platform" class="form-select">
                        <option value="">All Platform</option>
                        @foreach (['Meta Ads', 'Google Ads', 'TikTok Ads', 'LinkedIn Ads', 'Other'] as $platform)
                            <option value="{{ $platform }}" @selected(request('platform') === $platform)>{{ $platform }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-2 col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        @foreach (['planned', 'active', 'paused', 'done', 'cancelled'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-1 col-md-6">
                    <label class="form-label">Active</label>
                    <select name="is_active" class="form-select">
                        <option value="">All</option>
                        <option value="1" @selected(request('is_active') === '1')>Yes</option>
                        <option value="0" @selected(request('is_active') === '0')>No</option>
                    </select>
                </div>

                <div class="col-xl-1 col-md-6">
                    <label class="form-label">Rows</label>
                    <select name="per_page" class="form-select">
                        @foreach ([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-1 col-md-6">
                    <label class="form-label">Date</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>

                <div class="col-xl-12">
                    <div class="filter-action-row d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-1"></i> Apply Filter
                        </button>

                        <a href="{{ route('marketing.setup.ads.index') }}" class="btn btn-light border">
                            <i class="bi bi-arrow-clockwise me-1"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Ads List</h5>
                <p class="content-card-subtitle mb-0">
                    Ads akan terbaca otomatis di report kalau rentang tanggalnya overlap dengan periode report.
                </p>
            </div>

            <span class="badge rounded-pill bg-light text-dark border">
                {{ $ads->total() }} Data
            </span>
        </div>

        <div class="content-card-body p-0">
            <div class="table-responsive">
                <table class="table admin-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Ad</th>
                            <th>Campaign</th>
                            <th>Period</th>
                            <th>Budget</th>
                            <th>Status</th>
                            <th>Usage</th>
                            <th class="text-end" style="width: 180px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ads as $index => $ad)
                            @php
                                $statusClass = match($ad->status) {
                                    'planned' => 'bg-warning-subtle text-warning-emphasis',
                                    'active' => 'bg-success-subtle text-success-emphasis',
                                    'paused' => 'bg-secondary-subtle text-secondary-emphasis',
                                    'done' => 'bg-primary-subtle text-primary-emphasis',
                                    'cancelled' => 'bg-danger-subtle text-danger-emphasis',
                                    default => 'bg-light text-dark',
                                };
                            @endphp
                            <tr>
                                <td>{{ $ads->firstItem() + $index }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $ad->ad_name }}</div>
                                    <div class="small text-muted">{{ $ad->platform }}</div>
                                    @if($ad->objective)
                                        <div class="small text-muted mt-1 text-truncate-2">{{ $ad->objective }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-medium">{{ optional($ad->campaign)->name ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="fw-medium">
                                        {{ optional($ad->start_date)->format('d M Y') ?? '-' }}
                                        -
                                        {{ optional($ad->end_date)->format('d M Y') ?? '-' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">Rp{{ number_format((float) $ad->total_budget, 0, ',', '.') }}</div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <span class="badge rounded-pill {{ $statusClass }}">
                                            {{ ucfirst($ad->status) }}
                                        </span>
                                        <span class="badge rounded-pill {{ $ad->is_active ? 'bg-success-subtle text-success-emphasis' : 'bg-light text-dark border' }}">
                                            {{ $ad->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="small text-dark">Reports: <strong>{{ $ad->report_ads_count }}</strong></div>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-edit-accent open-edit-ad-modal-btn"
                                            data-id="{{ $ad->id }}"
                                            data-marketing_setup_campaign_id="{{ $ad->marketing_setup_campaign_id }}"
                                            data-platform="{{ $ad->platform }}"
                                            data-ad_name="{{ $ad->ad_name }}"
                                            data-slug="{{ $ad->slug }}"
                                            data-objective="{{ $ad->objective }}"
                                            data-start_date="{{ optional($ad->start_date)->toDateString() }}"
                                            data-end_date="{{ optional($ad->end_date)->toDateString() }}"
                                            data-total_budget="{{ (float) $ad->total_budget }}"
                                            data-status="{{ $ad->status }}"
                                            data-notes="{{ $ad->notes }}"
                                            data-is_active="{{ $ad->is_active ? 1 : 0 }}"
                                        >
                                            <i class="bi bi-pencil-square me-1"></i> Edit
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-danger open-delete-ad-modal-btn"
                                            data-id="{{ $ad->id }}"
                                            data-name="{{ $ad->ad_name }}"
                                        >
                                            <i class="bi bi-trash me-1"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state-box my-4">
                                        <div class="empty-state-icon"><i class="bi bi-badge-ad"></i></div>
                                        <div class="empty-state-title">No ad setup found</div>
                                        <div class="empty-state-subtitle">
                                            Belum ada data ads setup yang cocok dengan filter saat ini.
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($ads->hasPages())
            <div class="content-card-footer">
                {{ $ads->links() }}
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="adFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="adModalTitle">Add Ad</h5>
                    <p class="text-muted small mb-0 mt-1">Simpan data dasar ads untuk dipakai di report periodik.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-3">
                <div id="adFormAlert" class="alert alert-danger d-none mb-3"></div>

                <form id="adForm">
                    @csrf
                    <input type="hidden" id="adFormMethod" name="_method" value="POST">
                    <input type="hidden" id="adId">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Campaign</label>
                            <select name="marketing_setup_campaign_id" id="ad_marketing_setup_campaign_id" class="form-select">
                                <option value="">Select Campaign</option>
                                @foreach ($campaignOptions as $campaign)
                                    <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="error_marketing_setup_campaign_id"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Platform <span class="text-danger">*</span></label>
                            <select name="platform" id="ad_platform" class="form-select">
                                <option value="">Select Platform</option>
                                <option value="Meta Ads">Meta Ads</option>
                                <option value="Google Ads">Google Ads</option>
                                <option value="TikTok Ads">TikTok Ads</option>
                                <option value="LinkedIn Ads">LinkedIn Ads</option>
                                <option value="Other">Other</option>
                            </select>
                            <div class="invalid-feedback" id="error_platform"></div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Ad Name <span class="text-danger">*</span></label>
                            <input type="text" name="ad_name" id="ad_ad_name" class="form-control">
                            <div class="invalid-feedback" id="error_ad_name"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" id="ad_slug" class="form-control">
                            <div class="invalid-feedback" id="error_slug"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Objective</label>
                            <textarea name="objective" id="ad_objective" rows="3" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_objective"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="ad_start_date" class="form-control">
                            <div class="invalid-feedback" id="error_start_date"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="ad_end_date" class="form-control">
                            <div class="invalid-feedback" id="error_end_date"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Total Budget</label>
                            <input type="number" min="0" step="0.01" name="total_budget" id="ad_total_budget" class="form-control">
                            <div class="invalid-feedback" id="error_total_budget"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" id="ad_status" class="form-select">
                                <option value="planned">Planned</option>
                                <option value="active">Active</option>
                                <option value="paused">Paused</option>
                                <option value="done">Done</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <div class="invalid-feedback" id="error_status"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label d-block">State</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="ad_is_active" name="is_active" value="1">
                                <label class="form-check-label" for="ad_is_active">Active</label>
                            </div>
                            <div class="invalid-feedback d-block" id="error_is_active"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" id="ad_notes" rows="3" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_notes"></div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitAdBtn">
                    <span class="default-text"><i class="bi bi-floppy me-1"></i> Save</span>
                    <span class="loading-text d-none">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteAdModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold">Delete Ad</h5>
                    <p class="text-muted small mb-0 mt-1">Tindakan ini tidak bisa dibatalkan.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-3">
                <div class="delete-modal-box">
                    <div class="delete-modal-icon"><i class="bi bi-trash3"></i></div>
                    <div>
                        <div class="fw-semibold text-dark mb-1">Yakin mau hapus ad ini?</div>
                        <div class="text-muted small">Ad <span id="deleteAdName" class="fw-semibold text-dark"></span> akan dihapus permanen.</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteAdBtn">
                    <span class="default-delete-text"><i class="bi bi-trash me-1"></i> Delete</span>
                    <span class="loading-delete-text d-none">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Deleting...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const formModalEl = document.getElementById('adFormModal');
    const deleteModalEl = document.getElementById('deleteAdModal');
    const formModal = bootstrap.Modal.getOrCreateInstance(formModalEl);
    const deleteModal = bootstrap.Modal.getOrCreateInstance(deleteModalEl);

    let deleteId = null;

    document.getElementById('openCreateAdModalBtn')?.addEventListener('click', function () {
        resetAdForm();
        document.getElementById('adModalTitle').textContent = 'Add Ad';
        document.getElementById('adFormMethod').value = 'POST';
        document.getElementById('adId').value = '';
        formModal.show();
    });

    document.querySelectorAll('.open-edit-ad-modal-btn').forEach(button => {
        button.addEventListener('click', function () {
            resetAdForm();

            document.getElementById('adModalTitle').textContent = 'Edit Ad';
            document.getElementById('adFormMethod').value = 'PUT';
            document.getElementById('adId').value = this.dataset.id ?? '';

            document.getElementById('ad_marketing_setup_campaign_id').value = this.dataset.marketing_setup_campaign_id ?? '';
            document.getElementById('ad_platform').value = this.dataset.platform ?? '';
            document.getElementById('ad_ad_name').value = this.dataset.ad_name ?? '';
            document.getElementById('ad_slug').value = this.dataset.slug ?? '';
            document.getElementById('ad_objective').value = this.dataset.objective ?? '';
            document.getElementById('ad_start_date').value = this.dataset.start_date ?? '';
            document.getElementById('ad_end_date').value = this.dataset.end_date ?? '';
            document.getElementById('ad_total_budget').value = this.dataset.total_budget ?? '';
            document.getElementById('ad_status').value = this.dataset.status ?? 'planned';
            document.getElementById('ad_notes').value = this.dataset.notes ?? '';
            document.getElementById('ad_is_active').checked = String(this.dataset.is_active) === '1';

            formModal.show();
        });
    });

    document.querySelectorAll('.open-delete-ad-modal-btn').forEach(button => {
        button.addEventListener('click', function () {
            deleteId = this.dataset.id;
            document.getElementById('deleteAdName').textContent = this.dataset.name ?? '';
            deleteModal.show();
        });
    });

    document.getElementById('submitAdBtn')?.addEventListener('click', submitAdForm);
    document.getElementById('confirmDeleteAdBtn')?.addEventListener('click', deleteAd);

    function resetAdForm() {
        document.getElementById('adForm').reset();
        document.getElementById('adFormAlert').classList.add('d-none');
        document.getElementById('adFormAlert').textContent = '';

        document.querySelectorAll('#adForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));

        [
            'marketing_setup_campaign_id','platform','ad_name','slug','objective',
            'start_date','end_date','total_budget','status','notes','is_active'
        ].forEach(field => {
            const errorEl = document.getElementById(`error_${field}`);
            if (errorEl) errorEl.textContent = '';
        });
    }

    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const id = `toast-${Date.now()}`;
        const bgClass = type === 'success' ? 'text-bg-success' : 'text-bg-danger';
        const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill';

        container.insertAdjacentHTML('beforeend', `
            <div id="${id}" class="toast align-items-center ${bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${icon} me-2"></i>${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `);

        const toastEl = document.getElementById(id);
        const toast = new bootstrap.Toast(toastEl, { delay: 1600 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    function setSubmitLoading(isLoading) {
        const button = document.getElementById('submitAdBtn');
        button.disabled = isLoading;
        button.querySelector('.default-text').classList.toggle('d-none', isLoading);
        button.querySelector('.loading-text').classList.toggle('d-none', !isLoading);
    }

    function setDeleteLoading(isLoading) {
        const button = document.getElementById('confirmDeleteAdBtn');
        button.disabled = isLoading;
        button.querySelector('.default-delete-text').classList.toggle('d-none', isLoading);
        button.querySelector('.loading-delete-text').classList.toggle('d-none', !isLoading);
    }

    async function parseJsonSafe(response) {
        const text = await response.text();
        try {
            return text ? JSON.parse(text) : {};
        } catch (e) {
            return { success: false, message: text || 'Invalid server response.' };
        }
    }

    async function submitAdForm() {
        resetValidationOnly();
        setSubmitLoading(true);

        const id = document.getElementById('adId').value;
        const method = document.getElementById('adFormMethod').value;
        const url = method === 'PUT'
            ? @json(url('/marketing/setup/ads')) + '/' + id
            : @json(route('marketing.setup.ads.store'));

        const formData = new FormData(document.getElementById('adForm'));
        if (!document.getElementById('ad_is_active').checked) {
            formData.delete('is_active');
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await parseJsonSafe(response);

            if (!response.ok) {
                if (response.status === 422 && data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        const input = document.querySelector(`#adForm [name="${field}"]`);
                        const errorEl = document.getElementById(`error_${field}`);
                        if (input) input.classList.add('is-invalid');
                        if (errorEl) errorEl.textContent = data.errors[field][0];
                    });
                } else {
                    showFormAlert(data.message || 'Failed to save ad.');
                }
                return;
            }

            formModal.hide();
            showToast(data.message || 'Ad saved successfully.');
            setTimeout(() => window.location.reload(), 700);
        } catch (error) {
            showFormAlert('Network error. Please try again.');
        } finally {
            setSubmitLoading(false);
        }
    }

    async function deleteAd() {
        if (!deleteId) return;

        setDeleteLoading(true);

        try {
            const response = await fetch(@json(url('/marketing/setup/ads')) + '/' + deleteId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            const data = await parseJsonSafe(response);

            if (!response.ok) {
                showToast(data.message || 'Failed to delete ad.', 'danger');
                return;
            }

            deleteModal.hide();
            showToast(data.message || 'Ad deleted successfully.');
            setTimeout(() => window.location.reload(), 700);
        } catch (error) {
            showToast('Network error. Please try again.', 'danger');
        } finally {
            setDeleteLoading(false);
        }
    }

    function showFormAlert(message) {
        const alert = document.getElementById('adFormAlert');
        alert.textContent = message;
        alert.classList.remove('d-none');
    }

    function resetValidationOnly() {
        document.getElementById('adFormAlert').classList.add('d-none');
        document.getElementById('adFormAlert').textContent = '';
        document.querySelectorAll('#adForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('#adForm .invalid-feedback').forEach(el => el.textContent = '');
    }
});
</script>
@endpush