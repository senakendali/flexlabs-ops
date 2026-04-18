@extends('layouts.app-dashboard')

@section('title', 'Marketing Ads')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Marketing Ads</h4>
            <small class="text-muted">Manage ads performance summary under each marketing campaign.</small>
        </div>

        <button type="button" class="btn btn-primary" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Ads
        </button>
    </div>

    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;"></div>

    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('marketing.ads.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input
                        type="text"
                        name="search"
                        class="form-control form-control-sm"
                        placeholder="Search ad name, campaign, platform, or UTM..."
                        value="{{ request('search') }}"
                    >
                </div>

                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>Paused</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="platform" class="form-select form-select-sm">
                        <option value="">All Platform</option>
                        <option value="meta_ads" {{ request('platform') === 'meta_ads' ? 'selected' : '' }}>Meta Ads</option>
                        <option value="tiktok_ads" {{ request('platform') === 'tiktok_ads' ? 'selected' : '' }}>TikTok Ads</option>
                        <option value="google_ads" {{ request('platform') === 'google_ads' ? 'selected' : '' }}>Google Ads</option>
                    </select>
                </div>

                <div class="col-md-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                </div>

                <div class="col-md-auto">
                    <a href="{{ route('marketing.ads.index') }}" class="btn btn-sm btn-light border">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Ads</th>
                        <th>Campaign</th>
                        <th>Platform</th>
                        <th>Period</th>
                        <th class="text-end">Budget</th>
                        <th class="text-end">Spend</th>
                        <th class="text-end">Leads</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ads as $ad)
                        <tr>
                            <td>{{ $ads->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="fw-semibold">{{ $ad->ad_name ?: '-' }}</div>
                                <div class="small text-muted">
                                    CTR {{ number_format($ad->ctr, 2) }}% ·
                                    CPC Rp{{ number_format($ad->cpc, 0, ',', '.') }} ·
                                    CPL Rp{{ number_format($ad->cpl, 0, ',', '.') }}
                                </div>
                            </td>
                            <td>
                                <div>{{ $ad->campaign?->name ?? '-' }}</div>
                                @if ($ad->utm_campaign)
                                    <div class="small text-muted">{{ $ad->utm_campaign }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $ad->platform_label }}
                                </span>
                            </td>
                            <td>
                                <div class="small">
                                    {{ $ad->start_date?->format('d M Y') ?? '-' }}
                                    —
                                    {{ $ad->end_date?->format('d M Y') ?? '-' }}
                                </div>
                                @if ($ad->duration_days > 0)
                                    <div class="small text-muted">{{ $ad->duration_days }} days</div>
                                @endif
                            </td>
                            <td class="text-end">Rp{{ number_format($ad->budget ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end">Rp{{ number_format($ad->spend ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end">
                                <div>{{ number_format($ad->leads ?? 0, 0, ',', '.') }}</div>
                                <div class="small text-muted">
                                    Conv {{ number_format($ad->conversions ?? 0, 0, ',', '.') }}
                                </div>
                            </td>
                            <td>
                                @php
                                    $statusClass = match ($ad->status) {
                                        'active' => 'bg-success-subtle text-success border border-success-subtle',
                                        'paused' => 'bg-warning-subtle text-warning border border-warning-subtle',
                                        'completed' => 'bg-info-subtle text-info border border-info-subtle',
                                        default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">
                                    {{ ucfirst($ad->status) }}
                                </span>

                                @if (! $ad->is_active)
                                    <div class="small text-muted mt-1">Inactive</div>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        data-id="{{ $ad->id }}"
                                        onclick="viewAds(this)"
                                        title="View"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-id="{{ $ad->id }}"
                                        onclick="editAds(this)"
                                        title="Edit"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-id="{{ $ad->id }}"
                                        data-name="{{ e($ad->ad_name ?: 'this ads') }}"
                                        onclick="openDeleteModal(this)"
                                        title="Delete"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                No marketing ads found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($ads->hasPages())
            <div class="card-footer bg-white">
                {{ $ads->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Form Modal --}}
<div class="modal fade" id="adsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form id="adsForm">
            @csrf
            <input type="hidden" id="ads_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="adsModalTitle">Add Ads</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none" role="alert"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="marketing_campaign_id" class="form-label">Campaign</label>
                            <select id="marketing_campaign_id" class="form-select">
                                <option value="">Select Campaign</option>
                                @foreach ($campaigns as $campaign)
                                    <option value="{{ $campaign->id }}">
                                        {{ $campaign->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="error_marketing_campaign_id"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="platform" class="form-label">Platform <span class="text-danger">*</span></label>
                            <select id="platform" class="form-select">
                                <option value="">Select Platform</option>
                                <option value="meta_ads">Meta Ads</option>
                                <option value="tiktok_ads">TikTok Ads</option>
                                <option value="google_ads">Google Ads</option>
                            </select>
                            <div class="invalid-feedback" id="error_platform"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select id="status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="paused">Paused</option>
                                <option value="completed">Completed</option>
                            </select>
                            <div class="invalid-feedback" id="error_status"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="ad_name" class="form-label">Ads Name</label>
                            <input type="text" id="ad_name" class="form-control" placeholder="Example: Meta Lead Form Batch A">
                            <div class="invalid-feedback" id="error_ad_name"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" id="start_date" class="form-control">
                            <div class="invalid-feedback" id="error_start_date"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" id="end_date" class="form-control">
                            <div class="invalid-feedback" id="error_end_date"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="budget" class="form-label">Budget</label>
                            <input type="number" min="0" step="0.01" id="budget" class="form-control" placeholder="0">
                            <div class="invalid-feedback" id="error_budget"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="spend" class="form-label">Spend</label>
                            <input type="number" min="0" step="0.01" id="spend" class="form-control" placeholder="0">
                            <div class="invalid-feedback" id="error_spend"></div>
                        </div>

                        <div class="col-md-2">
                            <label for="impressions" class="form-label">Impressions</label>
                            <input type="number" min="0" id="impressions" class="form-control" placeholder="0">
                            <div class="invalid-feedback" id="error_impressions"></div>
                        </div>

                        <div class="col-md-2">
                            <label for="clicks" class="form-label">Clicks</label>
                            <input type="number" min="0" id="clicks" class="form-control" placeholder="0">
                            <div class="invalid-feedback" id="error_clicks"></div>
                        </div>

                        <div class="col-md-2">
                            <label for="leads" class="form-label">Leads</label>
                            <input type="number" min="0" id="leads" class="form-control" placeholder="0">
                            <div class="invalid-feedback" id="error_leads"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="conversions" class="form-label">Conversions</label>
                            <input type="number" min="0" id="conversions" class="form-control" placeholder="0">
                            <div class="invalid-feedback" id="error_conversions"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="pic_user_id" class="form-label">PIC</label>
                            <select id="pic_user_id" class="form-select">
                                <option value="">Select PIC</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="error_pic_user_id"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="source_type" class="form-label">Source Type</label>
                            <select id="source_type" class="form-select">
                                <option value="manual">Manual</option>
                                <option value="kommo_sync">Kommo Sync</option>
                                <option value="ads_api">Ads API</option>
                            </select>
                            <div class="invalid-feedback" id="error_source_type"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="external_reference" class="form-label">External Reference</label>
                            <input type="text" id="external_reference" class="form-control" placeholder="Optional external ID">
                            <div class="invalid-feedback" id="error_external_reference"></div>
                        </div>

                        <div class="col-md-2">
                            <label for="utm_source" class="form-label">UTM Source</label>
                            <input type="text" id="utm_source" class="form-control" placeholder="meta">
                            <div class="invalid-feedback" id="error_utm_source"></div>
                        </div>

                        <div class="col-md-2">
                            <label for="utm_campaign" class="form-label">UTM Campaign</label>
                            <input type="text" id="utm_campaign" class="form-control" placeholder="campaign name">
                            <div class="invalid-feedback" id="error_utm_campaign"></div>
                        </div>

                        <div class="col-md-2">
                            <label for="utm_content" class="form-label">UTM Content</label>
                            <input type="text" id="utm_content" class="form-control" placeholder="creative name">
                            <div class="invalid-feedback" id="error_utm_content"></div>
                        </div>

                        <div class="col-md-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" class="form-control" rows="4" placeholder="Additional notes..."></textarea>
                            <div class="invalid-feedback" id="error_notes"></div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" id="is_active" checked>
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
                        <span class="default-text">Save Ads</span>
                        <span class="loading-text d-none">Saving...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- View Modal --}}
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Ads Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div id="viewLoading" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="small text-muted mt-2">Loading data...</div>
                </div>

                <div id="viewContent" class="d-none">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="small text-muted">Campaign</div>
                            <div class="fw-semibold" id="view_campaign_name">-</div>
                        </div>

                        <div class="col-md-6">
                            <div class="small text-muted">Ads Name</div>
                            <div class="fw-semibold" id="view_ad_name">-</div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Platform</div>
                            <div id="view_platform">-</div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Status</div>
                            <div id="view_status">-</div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">PIC</div>
                            <div id="view_pic_name">-</div>
                        </div>

                        <div class="col-md-6">
                            <div class="small text-muted">Start Date</div>
                            <div id="view_start_date">-</div>
                        </div>

                        <div class="col-md-6">
                            <div class="small text-muted">End Date</div>
                            <div id="view_end_date">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Budget</div>
                            <div id="view_budget">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Spend</div>
                            <div id="view_spend">-</div>
                        </div>

                        <div class="col-md-2">
                            <div class="small text-muted">Impressions</div>
                            <div id="view_impressions">-</div>
                        </div>

                        <div class="col-md-2">
                            <div class="small text-muted">Clicks</div>
                            <div id="view_clicks">-</div>
                        </div>

                        <div class="col-md-2">
                            <div class="small text-muted">Leads</div>
                            <div id="view_leads">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Conversions</div>
                            <div id="view_conversions">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">CTR</div>
                            <div id="view_ctr">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">CPC</div>
                            <div id="view_cpc">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">CPL</div>
                            <div id="view_cpl">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Conversion Rate</div>
                            <div id="view_conversion_rate">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Source Type</div>
                            <div id="view_source_type">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">External Reference</div>
                            <div id="view_external_reference">-</div>
                        </div>

                        <div class="col-md-2">
                            <div class="small text-muted">UTM Source</div>
                            <div id="view_utm_source">-</div>
                        </div>

                        <div class="col-md-2">
                            <div class="small text-muted">UTM Campaign</div>
                            <div id="view_utm_campaign">-</div>
                        </div>

                        <div class="col-md-2">
                            <div class="small text-muted">UTM Content</div>
                            <div id="view_utm_content">-</div>
                        </div>

                        <div class="col-md-12">
                            <div class="small text-muted">Notes</div>
                            <div id="view_notes" class="border rounded p-3 bg-light-subtle">-</div>
                        </div>
                    </div>
                </div>

                <div id="viewError" class="alert alert-danger d-none mb-0"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Delete Ads</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to delete
                    <strong id="deleteAdsName"></strong>?
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
    const adsModalEl = document.getElementById('adsModal');
    const adsModal = new bootstrap.Modal(adsModalEl);
    const adsForm = document.getElementById('adsForm');
    const submitBtn = document.getElementById('submitBtn');
    const modalTitle = document.getElementById('adsModalTitle');
    const formAlert = document.getElementById('formAlert');

    const viewModalEl = document.getElementById('viewModal');
    const viewModal = new bootstrap.Modal(viewModalEl);

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteAdsNameEl = document.getElementById('deleteAdsName');

    const fields = {
        id: document.getElementById('ads_id'),
        marketing_campaign_id: document.getElementById('marketing_campaign_id'),
        platform: document.getElementById('platform'),
        ad_name: document.getElementById('ad_name'),
        start_date: document.getElementById('start_date'),
        end_date: document.getElementById('end_date'),
        budget: document.getElementById('budget'),
        spend: document.getElementById('spend'),
        impressions: document.getElementById('impressions'),
        clicks: document.getElementById('clicks'),
        leads: document.getElementById('leads'),
        conversions: document.getElementById('conversions'),
        status: document.getElementById('status'),
        notes: document.getElementById('notes'),
        is_active: document.getElementById('is_active'),
        pic_user_id: document.getElementById('pic_user_id'),
        source_type: document.getElementById('source_type'),
        external_reference: document.getElementById('external_reference'),
        utm_source: document.getElementById('utm_source'),
        utm_campaign: document.getElementById('utm_campaign'),
        utm_content: document.getElementById('utm_content'),
    };

    let isEditMode = false;
    let deleteAdsId = null;
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
            <div id="${id}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);

        const toastEl = document.getElementById(id);
        const toast = new bootstrap.Toast(toastEl, {
            delay: 3000
        });

        toast.show();

        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    function setLoading(button, isLoading, defaultSelector, loadingSelector) {
        button.disabled = isLoading;
        button.querySelector(defaultSelector)?.classList.toggle('d-none', isLoading);
        button.querySelector(loadingSelector)?.classList.toggle('d-none', !isLoading);
    }

    function resetValidation() {
        formAlert.classList.add('d-none');
        formAlert.textContent = '';

        Object.keys(fields).forEach((key) => {
            if (fields[key]) {
                fields[key].classList.remove('is-invalid');
            }

            const errorEl = document.getElementById(`error_${key}`);
            if (errorEl) {
                errorEl.textContent = '';
            }
        });
    }

    function resetForm() {
        adsForm.reset();
        resetValidation();

        isEditMode = false;
        fields.id.value = '';
        fields.status.value = 'draft';
        fields.source_type.value = 'manual';
        fields.is_active.checked = true;
        modalTitle.textContent = 'Add Ads';
    }

    function decodeHtml(value) {
        const txt = document.createElement('textarea');
        txt.innerHTML = value;
        return txt.value;
    }

    function openCreateModal() {
        resetForm();
        modalTitle.textContent = 'Add Ads';
        adsModal.show();
    }

    async function editAds(button) {
        const id = button.dataset.id;
        resetForm();
        modalTitle.textContent = 'Edit Ads';
        isEditMode = true;

        try {
            const response = await fetch(`{{ url('/marketing/ads') }}/${id}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Failed to load ads detail.');
            }

            const data = result.data;

            fields.id.value = data.id ?? '';
            fields.marketing_campaign_id.value = data.marketing_campaign_id ?? '';
            fields.platform.value = data.platform ?? '';
            fields.ad_name.value = data.ad_name ?? '';
            fields.start_date.value = data.start_date ?? '';
            fields.end_date.value = data.end_date ?? '';
            fields.budget.value = data.budget ?? 0;
            fields.spend.value = data.spend ?? 0;
            fields.impressions.value = data.impressions ?? 0;
            fields.clicks.value = data.clicks ?? 0;
            fields.leads.value = data.leads ?? 0;
            fields.conversions.value = data.conversions ?? 0;
            fields.status.value = data.status ?? 'draft';
            fields.notes.value = data.notes ?? '';
            fields.is_active.checked = Boolean(data.is_active);
            fields.pic_user_id.value = data.pic_user_id ?? '';
            fields.source_type.value = data.source_type ?? 'manual';
            fields.external_reference.value = data.external_reference ?? '';
            fields.utm_source.value = data.utm_source ?? '';
            fields.utm_campaign.value = data.utm_campaign ?? '';
            fields.utm_content.value = data.utm_content ?? '';

            adsModal.show();
        } catch (error) {
            showToast(error.message || 'Something went wrong.', 'danger');
        }
    }

    async function viewAds(button) {
        const id = button.dataset.id;

        document.getElementById('viewLoading').classList.remove('d-none');
        document.getElementById('viewContent').classList.add('d-none');
        document.getElementById('viewError').classList.add('d-none');
        document.getElementById('viewError').textContent = '';
        viewModal.show();

        try {
            const response = await fetch(`{{ url('/marketing/ads') }}/${id}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Failed to load ads detail.');
            }

            const data = result.data;

            document.getElementById('view_campaign_name').textContent = data.campaign_name || '-';
            document.getElementById('view_ad_name').textContent = data.ad_name || '-';
            document.getElementById('view_platform').textContent = data.platform_label || '-';
            document.getElementById('view_status').textContent = data.status || '-';
            document.getElementById('view_pic_name').textContent = data.pic_name || '-';
            document.getElementById('view_start_date').textContent = data.start_date || '-';
            document.getElementById('view_end_date').textContent = data.end_date || '-';
            document.getElementById('view_budget').textContent = formatRupiah(data.budget);
            document.getElementById('view_spend').textContent = formatRupiah(data.spend);
            document.getElementById('view_impressions').textContent = formatNumber(data.impressions);
            document.getElementById('view_clicks').textContent = formatNumber(data.clicks);
            document.getElementById('view_leads').textContent = formatNumber(data.leads);
            document.getElementById('view_conversions').textContent = formatNumber(data.conversions);
            document.getElementById('view_ctr').textContent = `${formatDecimal(data.ctr)}%`;
            document.getElementById('view_cpc').textContent = formatRupiah(data.cpc);
            document.getElementById('view_cpl').textContent = formatRupiah(data.cpl);
            document.getElementById('view_conversion_rate').textContent = `${formatDecimal(data.conversion_rate)}%`;
            document.getElementById('view_source_type').textContent = data.source_type_label || '-';
            document.getElementById('view_external_reference').textContent = data.external_reference || '-';
            document.getElementById('view_utm_source').textContent = data.utm_source || '-';
            document.getElementById('view_utm_campaign').textContent = data.utm_campaign || '-';
            document.getElementById('view_utm_content').textContent = data.utm_content || '-';
            document.getElementById('view_notes').textContent = data.notes || '-';

            document.getElementById('viewContent').classList.remove('d-none');
        } catch (error) {
            const errorEl = document.getElementById('viewError');
            errorEl.classList.remove('d-none');
            errorEl.textContent = error.message || 'Something went wrong.';
        } finally {
            document.getElementById('viewLoading').classList.add('d-none');
        }
    }

    async function submitForm(event) {
        event.preventDefault();
        resetValidation();

        const id = fields.id.value;
        const url = isEditMode
            ? `{{ url('/marketing/ads') }}/${id}`
            : `{{ route('marketing.ads.store') }}`;

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');

        if (isEditMode) {
            formData.append('_method', 'PUT');
        }

        formData.append('marketing_campaign_id', fields.marketing_campaign_id.value);
        formData.append('platform', fields.platform.value);
        formData.append('ad_name', fields.ad_name.value);
        formData.append('start_date', fields.start_date.value);
        formData.append('end_date', fields.end_date.value);
        formData.append('budget', fields.budget.value || 0);
        formData.append('spend', fields.spend.value || 0);
        formData.append('impressions', fields.impressions.value || 0);
        formData.append('clicks', fields.clicks.value || 0);
        formData.append('leads', fields.leads.value || 0);
        formData.append('conversions', fields.conversions.value || 0);
        formData.append('status', fields.status.value);
        formData.append('notes', fields.notes.value);
        formData.append('pic_user_id', fields.pic_user_id.value);
        formData.append('source_type', fields.source_type.value);
        formData.append('external_reference', fields.external_reference.value);
        formData.append('utm_source', fields.utm_source.value);
        formData.append('utm_campaign', fields.utm_campaign.value);
        formData.append('utm_content', fields.utm_content.value);

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

                throw new Error(data.message || 'Failed to save ads.');
            }

            adsModal.hide();
            showToast(data.message || (isEditMode ? 'Ads updated successfully.' : 'Ads created successfully.'));

            clearTimeout(reloadTimeout);
            reloadTimeout = setTimeout(() => window.location.reload(), 700);
        } catch (error) {
            formAlert.classList.remove('d-none');
            formAlert.textContent = error.message || 'Something went wrong.';
        } finally {
            setLoading(submitBtn, false, '.default-text', '.loading-text');
        }
    }

    function openDeleteModal(button) {
        deleteAdsId = button.dataset.id;
        deleteAdsNameEl.textContent = decodeHtml(button.dataset.name || '');
        deleteModal.show();
    }

    async function confirmDelete() {
        if (!deleteAdsId) return;

        setLoading(confirmDeleteBtn, true, '.default-delete-text', '.loading-delete-text');

        try {
            const response = await fetch(`{{ url('/marketing/ads') }}/${deleteAdsId}`, {
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
                throw new Error(data.message || 'Failed to delete ads.');
            }

            deleteModal.hide();
            showToast(data.message || 'Ads deleted successfully.');

            clearTimeout(reloadTimeout);
            reloadTimeout = setTimeout(() => window.location.reload(), 700);
        } catch (error) {
            showToast(error.message || 'Something went wrong.', 'danger');
        } finally {
            setLoading(confirmDeleteBtn, false, '.default-delete-text', '.loading-delete-text');
        }
    }

    function formatRupiah(value) {
        const number = Number(value || 0);
        return 'Rp' + number.toLocaleString('id-ID');
    }

    function formatNumber(value) {
        const number = Number(value || 0);
        return number.toLocaleString('id-ID');
    }

    function formatDecimal(value) {
        const number = Number(value || 0);
        return number.toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    adsForm.addEventListener('submit', submitForm);
    confirmDeleteBtn.addEventListener('click', confirmDelete);
    adsModalEl.addEventListener('hidden.bs.modal', resetForm);
</script>
@endpush