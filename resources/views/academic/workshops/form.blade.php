@extends('layouts.app-dashboard')

@section('title', ($submitMode === 'edit' ? 'Edit' : 'Create') . ' Workshop')

@section('content')
@php
    $isEdit = $submitMode === 'edit';
    $formAction = $isEdit
        ? route('academic.workshops.update', $workshop)
        : route('academic.workshops.store');

    $titleValue = old('title', $workshop->title ?? null);
    $slugValue = old('slug', $workshop->slug ?? null);
    $badgeValue = old('badge', $workshop->badge ?? null);
    $categoryValue = old('category', $workshop->category ?? null);
    $levelValue = old('level', $workshop->level ?? null);
    $durationValue = old('duration', $workshop->duration ?? null);

    $shortDescriptionValue = old('short_description', $workshop->short_description ?? null);
    $overviewValue = old('overview', $workshop->overview ?? null);
    $audienceValue = old('audience', $workshop->audience ?? null);

    $priceValue = old('price', $workshop->price ?? 0);
    $oldPriceValue = old('old_price', $workshop->old_price ?? null);
    $ratingValue = old('rating', $workshop->rating ?? 5);
    $ratingCountValue = old('rating_count', $workshop->rating_count ?? 0);

    $videoTypeValue = old('intro_video_type', $workshop->intro_video_type ?? 'youtube');
    $videoUrlValue = old('intro_video_url', $workshop->intro_video_url ?? null);

    $sortOrderValue = old('sort_order', $workshop->sort_order ?? 0);
    $isActiveValue = old('is_active', $workshop->is_active ?? true);

    $benefitItems = old('benefits', $benefits ?? ['']);
@endphp

<div class="container-fluid px-4 py-4 workshops-form-page">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic</div>
                <h1 class="page-title mb-2">{{ $isEdit ? 'Edit Workshop' : 'Create Workshop' }}</h1>
                <p class="page-subtitle mb-0">
                    Atur informasi workshop, pricing, preview video, benefit pembelajaran, dan status publikasi dalam satu form yang rapi.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('academic.workshops.index') }}" class="btn btn-light border">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>

                @if ($isEdit)
                    <a href="{{ route('academic.workshops.show', $workshop) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-eye me-1"></i> Show
                    </a>
                @endif

                <button type="button" id="saveDraftBtn" class="btn btn-save-draft">
                    <i class="bi bi-save me-1"></i> Save Draft
                </button>

                <button type="button" id="submitWorkshopBtnTop" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> {{ $isEdit ? 'Update Workshop' : 'Create Workshop' }}
                </button>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;"></div>

    <div class="content-card mb-4">
        <div class="content-card-body">
            <div class="row g-3 align-items-stretch">
                <div class="col-lg-8 d-flex">
                    <div class="progress-summary-wrap w-100">
                        <div class="progress-summary-top d-flex justify-content-between align-items-center gap-3 mb-2 flex-wrap">
                            <div>
                                <div class="progress-summary-label">Form Progress</div>
                                <div class="progress-summary-subtitle">
                                    Lengkapi informasi utama workshop, konten, media preview, dan status publikasi agar data siap disimpan.
                                </div>
                            </div>
                            <div class="progress-summary-count">
                                <span id="completedSectionCount">0</span> dari <span id="totalSectionCount">4</span> bagian sudah siap
                            </div>
                        </div>

                        <div class="progress progress-modern" role="progressbar" aria-label="Workshop form progress">
                            <div id="sectionProgressBar" class="progress-bar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 d-flex">
                    <div class="status-pill-card w-100">
                        <div class="status-pill-label">Workshop Status</div>
                        <div class="status-pill-value" id="liveStatusText">
                            {{ $isActiveValue ? 'Active' : 'Draft / Inactive' }}
                        </div>
                        <div class="status-pill-help" id="liveSummaryText">
                            {{ $isActiveValue ? 'Workshop siap ditampilkan di halaman public.' : 'Workshop masih tersimpan sebagai draft dan tidak tampil di halaman public.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="workshopForm" action="{{ $formAction }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-xl-3">
                <div class="section-nav-card sticky-top" style="top: 92px;">
                    <div class="section-nav-header">
                        <div class="section-nav-title">Form Sections</div>
                        <div class="section-nav-subtitle">Lengkapi seluruh bagian workshop.</div>
                    </div>

                    <div class="nav flex-column nav-pills custom-section-nav" id="workshop-section-tab" role="tablist" aria-orientation="vertical">
                        <button class="nav-link active" id="tab-overview-btn" data-bs-toggle="pill" data-bs-target="#tab-overview" type="button" role="tab">
                            <span class="nav-link-content">
                                <span class="nav-link-icon"><i class="bi bi-grid-1x2-fill"></i></span>
                                <span class="nav-link-text">
                                    <span class="nav-link-title">Overview</span>
                                    <span class="nav-link-subtitle">Title, slug, category</span>
                                </span>
                            </span>
                            <span class="section-check" data-section-indicator="overview"><i class="bi bi-circle"></i></span>
                        </button>

                        <button class="nav-link" id="tab-content-btn" data-bs-toggle="pill" data-bs-target="#tab-content" type="button" role="tab">
                            <span class="nav-link-content">
                                <span class="nav-link-icon"><i class="bi bi-card-text"></i></span>
                                <span class="nav-link-text">
                                    <span class="nav-link-title">Content</span>
                                    <span class="nav-link-subtitle">Description, audience, benefits</span>
                                </span>
                            </span>
                            <span class="section-check" data-section-indicator="content"><i class="bi bi-circle"></i></span>
                        </button>

                        <button class="nav-link" id="tab-media-btn" data-bs-toggle="pill" data-bs-target="#tab-media" type="button" role="tab">
                            <span class="nav-link-content">
                                <span class="nav-link-icon"><i class="bi bi-play-btn-fill"></i></span>
                                <span class="nav-link-text">
                                    <span class="nav-link-title">Media & Pricing</span>
                                    <span class="nav-link-subtitle">Price, image, video</span>
                                </span>
                            </span>
                            <span class="section-check" data-section-indicator="media"><i class="bi bi-circle"></i></span>
                        </button>

                        <button class="nav-link" id="tab-status-btn" data-bs-toggle="pill" data-bs-target="#tab-status" type="button" role="tab">
                            <span class="nav-link-content">
                                <span class="nav-link-icon"><i class="bi bi-clipboard-check-fill"></i></span>
                                <span class="nav-link-text">
                                    <span class="nav-link-title">Status</span>
                                    <span class="nav-link-subtitle">Publish settings & order</span>
                                </span>
                            </span>
                            <span class="section-check" data-section-indicator="status"><i class="bi bi-circle"></i></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-xl-9">
                <div class="tab-content" id="workshop-section-tabContent">
                    <div class="tab-pane fade show active" id="tab-overview" role="tabpanel" aria-labelledby="tab-overview-btn">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header">
                                <div>
                                    <h5 class="content-card-title mb-1">Workshop Overview</h5>
                                    <p class="content-card-subtitle mb-0">
                                        Isi informasi dasar workshop yang akan ditampilkan di halaman public.
                                    </p>
                                </div>
                                <div class="section-status-badge" data-section-badge="overview">
                                    <i class="bi bi-hourglass-split me-1"></i> Need Input
                                </div>
                            </div>

                            <div class="content-card-body">
                                <div class="row g-3">
                                    <div class="col-lg-8">
                                        <label class="form-label">Title <span class="text-danger">*</span></label>
                                        <input type="text" name="title" id="title" class="form-control section-overview" value="{{ $titleValue }}" placeholder="Contoh: Figma to Elementor Low Code Development">
                                        <div class="invalid-feedback error-text" data-error-for="title"></div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">Badge</label>
                                        <input type="text" name="badge" class="form-control section-overview" value="{{ $badgeValue }}" placeholder="Contoh: Design to Build Workshop">
                                        <div class="invalid-feedback error-text" data-error-for="badge"></div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label">Slug <span class="text-danger">*</span></label>
                                        <input type="text" name="slug" id="slug" class="form-control section-overview" value="{{ $slugValue }}" placeholder="auto-generated-from-title">
                                        <div class="invalid-feedback error-text" data-error-for="slug"></div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label">Category</label>
                                        <input type="text" name="category" class="form-control section-overview" value="{{ $categoryValue }}" placeholder="Contoh: UI Implementation">
                                        <div class="invalid-feedback error-text" data-error-for="category"></div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label">Level</label>
                                        <input type="text" name="level" class="form-control section-overview" value="{{ $levelValue }}" placeholder="Contoh: Beginner - Intermediate">
                                        <div class="invalid-feedback error-text" data-error-for="level"></div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label">Duration</label>
                                        <input type="text" name="duration" class="form-control section-overview" value="{{ $durationValue }}" placeholder="Contoh: 2 Jam">
                                        <div class="invalid-feedback error-text" data-error-for="duration"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-content" role="tabpanel" aria-labelledby="tab-content-btn">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header">
                                <div>
                                    <h5 class="content-card-title mb-1">Content & Audience</h5>
                                    <p class="content-card-subtitle mb-0">
                                        Isi short description, overview, audience, dan daftar benefit workshop.
                                    </p>
                                </div>
                                <div class="section-status-badge" data-section-badge="content">
                                    <i class="bi bi-hourglass-split me-1"></i> Need Input
                                </div>
                            </div>

                            <div class="content-card-body">
                                <div class="row g-3 mb-4">
                                    <div class="col-12">
                                        <label class="form-label">Short Description</label>
                                        <textarea name="short_description" class="form-control section-content" rows="3" placeholder="Deskripsi singkat workshop">{{ $shortDescriptionValue }}</textarea>
                                        <div class="invalid-feedback error-text" data-error-for="short_description"></div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Overview</label>
                                        <textarea name="overview" class="form-control section-content" rows="6" placeholder="Penjelasan lengkap workshop">{{ $overviewValue }}</textarea>
                                        <div class="invalid-feedback error-text" data-error-for="overview"></div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Audience</label>
                                        <textarea name="audience" class="form-control section-content" rows="4" placeholder="Target peserta workshop">{{ $audienceValue }}</textarea>
                                        <div class="invalid-feedback error-text" data-error-for="audience"></div>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                                    <div>
                                        <h6 class="mb-1">Benefits</h6>
                                        <div class="text-muted small">Isi poin “Yang akan dipelajari”.</div>
                                    </div>

                                    <button type="button" class="btn btn-outline-primary" id="addBenefitBtn">
                                        <i class="bi bi-plus-circle me-1"></i> Add Benefit
                                    </button>
                                </div>

                                <div id="benefitsWrapper" class="d-grid gap-3">
                                    @foreach ($benefitItems as $benefit)
                                        <div class="benefit-item border rounded-4 p-3 bg-light-subtle">
                                            <div class="d-flex gap-2 align-items-start">
                                                <div class="flex-grow-1">
                                                    <textarea name="benefits[]" class="form-control benefit-input section-content" rows="2" placeholder="Contoh: Paham interface dan timeline Adobe Premiere">{{ $benefit }}</textarea>
                                                </div>

                                                <button type="button" class="btn btn-outline-danger btn-remove-benefit">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="invalid-feedback error-text d-block" data-error-for="benefits"></div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-media" role="tabpanel" aria-labelledby="tab-media-btn">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header">
                                <div>
                                    <h5 class="content-card-title mb-1">Media & Pricing</h5>
                                    <p class="content-card-subtitle mb-0">
                                        Atur harga, rating, thumbnail workshop, dan video preview.
                                    </p>
                                </div>
                                <div class="section-status-badge" data-section-badge="media">
                                    <i class="bi bi-hourglass-split me-1"></i> Need Input
                                </div>
                            </div>

                            <div class="content-card-body">
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <label class="form-label">Price <span class="text-danger">*</span></label>
                                        <input type="number" min="0" step="0.01" name="price" class="form-control section-media" value="{{ $priceValue }}">
                                        <div class="invalid-feedback error-text" data-error-for="price"></div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label">Old Price</label>
                                        <input type="number" min="0" step="0.01" name="old_price" class="form-control section-media" value="{{ $oldPriceValue }}">
                                        <div class="invalid-feedback error-text" data-error-for="old_price"></div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label">Rating <span class="text-danger">*</span></label>
                                        <input type="number" min="0" max="5" name="rating" class="form-control section-media" value="{{ $ratingValue }}">
                                        <div class="invalid-feedback error-text" data-error-for="rating"></div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label">Rating Count <span class="text-danger">*</span></label>
                                        <input type="number" min="0" name="rating_count" class="form-control section-media" value="{{ $ratingCountValue }}">
                                        <div class="invalid-feedback error-text" data-error-for="rating_count"></div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Workshop Image</label>
                                        <input
                                            type="file"
                                            name="image"
                                            class="form-control section-media"
                                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                        >
                                        <div class="form-text">
                                            Upload file JPG, PNG, atau WEBP. Maksimal 2 MB.
                                        </div>
                                        <div class="invalid-feedback error-text" data-error-for="image"></div>

                                        @if ($isEdit && $workshop->image)
                                            <div class="mt-3">
                                                <div class="small text-muted mb-2">Current Image</div>
                                                <div class="border rounded-4 p-2 bg-light">
                                                    <img
                                                        src="{{ asset($workshop->image) }}"
                                                        alt="{{ $workshop->title }}"
                                                        class="img-fluid rounded-3"
                                                        style="max-height: 220px; object-fit: cover;"
                                                    >
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">Intro Video Type <span class="text-danger">*</span></label>
                                        <select name="intro_video_type" id="intro_video_type" class="form-select section-media">
                                            <option value="youtube" @selected($videoTypeValue === 'youtube')>YouTube</option>
                                            <option value="upload" @selected($videoTypeValue === 'upload')>Upload / Direct URL</option>
                                        </select>
                                        <div class="invalid-feedback error-text" data-error-for="intro_video_type"></div>
                                    </div>

                                    <div class="col-lg-8">
                                        <label class="form-label">Intro Video URL</label>
                                        <input type="text" name="intro_video_url" class="form-control section-media" value="{{ $videoUrlValue }}" placeholder="https://www.youtube.com/embed/...">
                                        <div class="invalid-feedback error-text" data-error-for="intro_video_url"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-status" role="tabpanel" aria-labelledby="tab-status-btn">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header">
                                <div>
                                    <h5 class="content-card-title mb-1">Publish Settings</h5>
                                    <p class="content-card-subtitle mb-0">
                                        Atur urutan tampil workshop dan status aktifnya.
                                    </p>
                                </div>
                                <div class="section-status-badge" data-section-badge="status">
                                    <i class="bi bi-hourglass-split me-1"></i> Need Input
                                </div>
                            </div>

                            <div class="content-card-body">
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <label class="form-label">Sort Order</label>
                                        <input type="number" min="0" name="sort_order" class="form-control section-status" value="{{ $sortOrderValue }}">
                                        <div class="invalid-feedback error-text" data-error-for="sort_order"></div>
                                    </div>

                                    <div class="col-lg-6 d-flex align-items-end">
                                        <div class="form-switch-card w-100">
                                            <div>
                                                <div class="form-switch-title">Publish Workshop</div>
                                                <div class="form-switch-subtitle">
                                                    Aktifkan jika workshop ingin langsung tampil di halaman public.
                                                </div>
                                            </div>
                                            <div class="form-check form-switch m-0">
                                                <input class="form-check-input section-status" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked($isActiveValue)>
                                            </div>
                                        </div>
                                        <div class="invalid-feedback error-text d-block" data-error-for="is_active"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($isEdit)
                            <div class="content-card mb-4">
                                <div class="content-card-header section-card-header">
                                    <div>
                                        <h5 class="content-card-title mb-1">Quick Info</h5>
                                        <p class="content-card-subtitle mb-0">Informasi singkat workshop saat ini.</p>
                                    </div>
                                    <div class="section-status-badge optional">
                                        <i class="bi bi-dash-circle me-1"></i> Optional
                                    </div>
                                </div>

                                <div class="content-card-body">
                                    <div class="simple-list">
                                        <div class="simple-list-item">
                                            <div class="simple-list-title">Created At</div>
                                            <div class="simple-list-meta">{{ optional($workshop->created_at)->format('d M Y H:i') ?? '-' }}</div>
                                        </div>
                                        <div class="simple-list-item">
                                            <div class="simple-list-title">Updated At</div>
                                            <div class="simple-list-meta">{{ optional($workshop->updated_at)->format('d M Y H:i') ?? '-' }}</div>
                                        </div>
                                        <div class="simple-list-item">
                                            <div class="simple-list-title">Current Status</div>
                                            <div class="simple-list-meta">{{ $workshop->is_active ? 'Active' : 'Inactive' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="content-card">
                    <div class="content-card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 w-100">
                            <div class="footer-note">
                                <div class="footer-note-title">Final Check</div>
                                <div class="footer-note-subtitle">
                                    Pastikan title, pricing, video preview, dan benefit workshop sudah sesuai sebelum menyimpan data.
                                </div>
                            </div>

                            <div class="ms-auto d-flex gap-2 flex-wrap justify-content-end">
                                <a href="{{ route('academic.workshops.index') }}" class="btn btn-light border">Cancel</a>

                                <button type="button" id="saveDraftBtnBottom" class="btn btn-save-draft">
                                    <i class="bi bi-save me-1"></i> Save Draft
                                </button>

                                <button type="button" id="submitWorkshopBtnBottom" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-1"></i> {{ $isEdit ? 'Update Workshop' : 'Create Workshop' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<template id="benefitTemplate">
    <div class="benefit-item border rounded-4 p-3 bg-light-subtle">
        <div class="d-flex gap-2 align-items-start">
            <div class="flex-grow-1">
                <textarea name="benefits[]" class="form-control benefit-input section-content" rows="2" placeholder="Contoh: Paham interface dan timeline Adobe Premiere"></textarea>
            </div>

            <button type="button" class="btn btn-outline-danger btn-remove-benefit">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</template>
@endsection

@push('styles')
<style>
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('workshopForm');
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const saveDraftBtnBottom = document.getElementById('saveDraftBtnBottom');
    const submitWorkshopBtnTop = document.getElementById('submitWorkshopBtnTop');
    const submitWorkshopBtnBottom = document.getElementById('submitWorkshopBtnBottom');
    const toastContainer = document.getElementById('toastContainer');
    const addBenefitBtn = document.getElementById('addBenefitBtn');
    const benefitsWrapper = document.getElementById('benefitsWrapper');
    const benefitTemplate = document.getElementById('benefitTemplate');
    const titleField = document.getElementById('title');
    const slugField = document.getElementById('slug');
    const isActiveField = document.getElementById('is_active');
    const liveStatusText = document.getElementById('liveStatusText');
    const liveSummaryText = document.getElementById('liveSummaryText');
    const completedSectionCount = document.getElementById('completedSectionCount');
    const totalSectionCount = document.getElementById('totalSectionCount');
    const sectionProgressBar = document.getElementById('sectionProgressBar');

    if (!form) return;

    function showToast(message, type = 'success') {
        if (!toastContainer || typeof bootstrap === 'undefined') return;

        const toastId = 'toast-' + Date.now();
        const bgClass = {
            success: 'bg-success',
            danger: 'bg-danger',
            warning: 'bg-warning text-dark',
            info: 'bg-info text-dark'
        }[type] || 'bg-success';

        const closeBtnClass = (type === 'warning' || type === 'info')
            ? 'btn-close me-2 m-auto'
            : 'btn-close btn-close-white me-2 m-auto';

        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="${closeBtnClass}" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHtml);

        const toastEl = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
        toast.show();

        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    }

    function clearValidationErrors() {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.error-text').forEach(el => el.textContent = '');
    }

    function applyValidationErrors(errors) {
        Object.entries(errors).forEach(([key, messages]) => {
            const message = Array.isArray(messages) ? messages[0] : messages;

            if (key.startsWith('benefits.')) {
                const holder = form.querySelector('[data-error-for="benefits"]');
                if (holder && !holder.textContent) holder.textContent = message;
                return;
            }

            const field = form.querySelector(`[name="${cssEscapeName(key)}"]`);
            if (field) field.classList.add('is-invalid');

            const errorHolder = form.querySelector(`[data-error-for="${key}"]`);
            if (errorHolder) errorHolder.textContent = message;
        });
    }

    function cssEscapeName(name) {
        return name.replaceAll('[', '\\[').replaceAll(']', '\\]');
    }

    function slugify(text) {
        return String(text || '')
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    function bindBenefitActions(scope = document) {
        scope.querySelectorAll('.btn-remove-benefit').forEach((button) => {
            button.onclick = () => {
                const item = button.closest('.benefit-item');
                if (!item) return;

                if (benefitsWrapper.querySelectorAll('.benefit-item').length <= 1) {
                    const textarea = item.querySelector('textarea');
                    if (textarea) textarea.value = '';
                    refreshProgress();
                    return;
                }

                item.remove();
                refreshProgress();
            };
        });
    }

    function updateLiveStatus() {
        if (!liveStatusText || !liveSummaryText || !isActiveField) return;

        liveStatusText.textContent = isActiveField.checked ? 'Active' : 'Draft / Inactive';
        liveSummaryText.textContent = isActiveField.checked
            ? 'Workshop siap ditampilkan di halaman public.'
            : 'Workshop masih tersimpan sebagai draft dan tidak tampil di halaman public.';
    }

    function isFilled(value) {
        return value !== null && value !== undefined && String(value).trim() !== '';
    }

    function checkOverviewState() {
        const title = form.querySelector('[name="title"]')?.value;
        const slug = form.querySelector('[name="slug"]')?.value;
        return isFilled(title) && isFilled(slug) ? 'completed' : 'incomplete';
    }

    function checkContentState() {
        const shortDescription = form.querySelector('[name="short_description"]')?.value;
        const overview = form.querySelector('[name="overview"]')?.value;
        const benefits = Array.from(form.querySelectorAll('[name="benefits[]"]')).some((el) => isFilled(el.value));
        return (isFilled(shortDescription) || isFilled(overview)) && benefits ? 'completed' : 'incomplete';
    }

    function checkMediaState() {
        const price = form.querySelector('[name="price"]')?.value;
        const rating = form.querySelector('[name="rating"]')?.value;
        const ratingCount = form.querySelector('[name="rating_count"]')?.value;
        const introVideoType = form.querySelector('[name="intro_video_type"]')?.value;
        const introVideoUrl = form.querySelector('[name="intro_video_url"]')?.value;
        return (isFilled(price) && isFilled(rating) && isFilled(ratingCount) && isFilled(introVideoType) && isFilled(introVideoUrl))
            ? 'completed'
            : 'incomplete';
    }

    function checkStatusState() {
        const sortOrder = form.querySelector('[name="sort_order"]')?.value;
        return isFilled(sortOrder) ? 'completed' : 'incomplete';
    }

    function updateSectionUI(sectionKey, state) {
        const indicator = document.querySelector(`[data-section-indicator="${sectionKey}"]`);
        const badge = document.querySelector(`[data-section-badge="${sectionKey}"]`);

        if (indicator) {
            indicator.classList.remove('completed', 'optional');

            if (state === 'completed') {
                indicator.classList.add('completed');
                indicator.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
            } else {
                indicator.innerHTML = '<i class="bi bi-circle"></i>';
            }
        }

        if (badge) {
            badge.classList.remove('completed', 'optional');

            if (state === 'completed') {
                badge.classList.add('completed');
                badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Ready';
            } else {
                badge.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Need Input';
            }
        }
    }

    function refreshProgress() {
        const sections = {
            overview: checkOverviewState(),
            content: checkContentState(),
            media: checkMediaState(),
            status: checkStatusState(),
        };

        const filledCount = Object.values(sections).filter((state) => state === 'completed').length;
        const totalCount = Object.keys(sections).length;
        const percent = totalCount > 0 ? Math.round((filledCount / totalCount) * 100) : 0;

        Object.entries(sections).forEach(([key, state]) => updateSectionUI(key, state));
        completedSectionCount.textContent = String(filledCount);
        totalSectionCount.textContent = String(totalCount);
        sectionProgressBar.style.width = `${percent}%`;
    }

    function refreshAll() {
        updateLiveStatus();
        refreshProgress();
    }

    async function parseResponse(response) {
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) return await response.json();

        const text = await response.text();
        return { success: false, message: text || 'Unexpected server response.' };
    }

    async function submitForm(forceDraft = false) {
        clearValidationErrors();

        const formData = new FormData(form);

        if (forceDraft) {
            formData.set('is_active', '0');
        } else {
            formData.set('is_active', isActiveField?.checked ? '1' : '0');
        }

        const submitButtons = [saveDraftBtn, saveDraftBtnBottom, submitWorkshopBtnTop, submitWorkshopBtnBottom];
        submitButtons.forEach(btn => btn && (btn.disabled = true));

        const activeButtons = forceDraft
            ? [saveDraftBtn, saveDraftBtnBottom]
            : [submitWorkshopBtnTop, submitWorkshopBtnBottom];

        activeButtons.forEach((btn) => {
            if (btn) {
                btn.dataset.originalHtml = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...';
            }
        });

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: formData,
            });

            const data = await parseResponse(response);

            if (!response.ok) {
                if (response.status === 422 && data.errors) {
                    applyValidationErrors(data.errors);
                }
                showToast(data.message || 'Terjadi kesalahan saat menyimpan workshop.', 'danger');
                return;
            }

            showToast(
                data.message || (forceDraft ? 'Workshop berhasil disimpan sebagai draft.' : 'Workshop berhasil disimpan.'),
                'success'
            );

            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 900);
            }
        } catch (error) {
            showToast('Terjadi kesalahan saat mengirim data.', 'danger');
        } finally {
            submitButtons.forEach(btn => btn && (btn.disabled = false));
            activeButtons.forEach((btn) => {
                if (btn && btn.dataset.originalHtml) btn.innerHTML = btn.dataset.originalHtml;
            });
        }
    }

    addBenefitBtn?.addEventListener('click', () => {
        const fragment = benefitTemplate.content.cloneNode(true);
        benefitsWrapper.appendChild(fragment);
        bindBenefitActions(benefitsWrapper);
        refreshProgress();
    });

    titleField?.addEventListener('input', () => {
        if (!slugField.dataset.manuallyEdited) {
            slugField.value = slugify(titleField.value);
        }
        refreshProgress();
    });

    slugField?.addEventListener('input', () => {
        slugField.dataset.manuallyEdited = 'true';
    });

    [saveDraftBtn, saveDraftBtnBottom].forEach((btn) => btn?.addEventListener('click', () => submitForm(true)));
    [submitWorkshopBtnTop, submitWorkshopBtnBottom].forEach((btn) => btn?.addEventListener('click', () => submitForm(false)));

    bindBenefitActions();

    form.querySelectorAll('input, textarea, select').forEach((el) => {
        el.addEventListener('input', refreshAll);
        el.addEventListener('change', refreshAll);
    });

    refreshAll();
});
</script>
@endpush