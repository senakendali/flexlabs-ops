@extends('layouts.app-dashboard')

@section('title', ($submitMode === 'edit' ? 'Edit' : 'Create') . ' Marketing Report')

@section('content')
@php
    $isEdit = $submitMode === 'edit';
    $formAction = $isEdit
        ? route('marketing.reports.update', $report)
        : route('marketing.reports.store');

    $formMethod = $isEdit ? 'PUT' : 'POST';

    $campaignRows = old('campaigns', isset($campaigns) && count($campaigns) ? $campaigns->toArray() : []);
    $adRows = old('ads', isset($ads) && count($ads) ? $ads->toArray() : []);
    $eventRows = old('events', isset($events) && count($events) ? $events->toArray() : []);

    $periodTypeOptions = [
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
    ];

    $statusOptions = [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ];

    $campaignStatusOptions = [
        'planned' => 'Planned',
        'on_progress' => 'On Progress',
        'review' => 'Review',
        'done' => 'Done',
    ];

    $adStatusOptions = [
        'active' => 'Active',
        'paused' => 'Paused',
        'review' => 'Review',
        'done' => 'Done',
    ];

    $eventTypeOptions = [
        'owned_event' => 'Owned Event',
        'external_event' => 'External Event',
        'participated_event' => 'Participated Event',
        'trial_class' => 'Trial Class',
        'workshop' => 'Workshop',
        'info_session' => 'Info Session',
    ];

    $eventStatusOptions = [
        'planned' => 'Planned',
        'scheduled' => 'Scheduled',
        'open_registration' => 'Open Registration',
        'confirmed' => 'Confirmed',
        'done' => 'Done',
        'cancelled' => 'Cancelled',
    ];
@endphp

<div class="container-fluid px-4 py-4 marketing-report-form-page">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Marketing Reports</div>
                <h1 class="page-title mb-2">
                    {{ $isEdit ? 'Edit Marketing Report' : 'Create Marketing Report' }}
                </h1>
                <p class="page-subtitle mb-0">
                    Lengkapi setiap komponen report untuk menyusun ringkasan marketing yang rapi dan mudah dibaca.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('marketing.reports.index') }}" class="btn btn-light border">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
                <button type="button" id="saveDraftBtn" class="btn btn-save-draft">
                    <i class="bi bi-save me-1"></i> Save Draft
                </button>
                <button type="button" id="submitReportBtn" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> {{ $isEdit ? 'Update Report' : 'Create Report' }}
                </button>
            </div>
        </div>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 1090;"
    ></div>

    <div class="content-card mb-4">
        <div class="content-card-body">
            <div class="row g-3 align-items-stretch report-summary-row">
                <div class="col-lg-8 d-flex">
                    <div class="progress-summary-wrap w-100">
                        <div class="progress-summary-top d-flex justify-content-between align-items-center gap-3 mb-2">
                            <div>
                                <div class="progress-summary-label">Section Progress</div>
                                <div class="progress-summary-subtitle">
                                    Pantau kesiapan tiap komponen report. Komponen optional tanpa data tidak dianggap kurang.
                                </div>
                            </div>
                            <div class="progress-summary-count">
                                <span id="completedSectionCount">0</span> dari <span id="totalSectionCount">6</span> komponen report sudah diisi
                            </div>
                        </div>

                        <div class="progress progress-modern" role="progressbar" aria-label="Section completion progress">
                            <div id="sectionProgressBar" class="progress-bar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 d-flex">
                    <div class="status-pill-card w-100">
                        <div class="status-pill-label">Report Status</div>
                        <div class="status-pill-value" id="liveStatusText">
                            {{ old('status', $report->status ?? 'draft') ? ucfirst(old('status', $report->status ?? 'draft')) : 'Draft' }}
                        </div>
                        <div class="status-pill-help">
                            Status report mengikuti pilihan saat ini. Komponen optional seperti campaign, ads, atau event tetap aman meski periode ini tidak menggunakannya.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="marketingReportForm"
          action="{{ $formAction }}"
          method="POST"
          data-submit-mode="{{ $submitMode }}">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-xl-3">
                <div class="section-nav-card sticky-top" style="top: 92px;">
                    <div class="section-nav-header">
                        <div class="section-nav-title">Form Sections</div>
                        <div class="section-nav-subtitle">Lengkapi seluruh bagian report.</div>
                    </div>

                    <div class="nav flex-column nav-pills custom-section-nav" id="report-section-tab" role="tablist" aria-orientation="vertical">
                        <button class="nav-link active" id="tab-overview-btn" data-bs-toggle="pill" data-bs-target="#tab-overview" type="button" role="tab">
                            <span class="nav-link-content">
                                <span class="nav-link-icon"><i class="bi bi-grid-1x2-fill"></i></span>
                                <span class="nav-link-text">
                                    <span class="nav-link-title">Overview</span>
                                    <span class="nav-link-subtitle">Informasi utama report</span>
                                </span>
                            </span>
                            <span class="section-check" data-section-indicator="overview">
                                <i class="bi bi-circle"></i>
                            </span>
                        </button>

                        <button class="nav-link" id="tab-campaign-btn" data-bs-toggle="pill" data-bs-target="#tab-campaign" type="button" role="tab">
                            <span class="nav-link-content">
                                <span class="nav-link-icon"><i class="bi bi-megaphone-fill"></i></span>
                                <span class="nav-link-text">
                                    <span class="nav-link-title">Campaign Plan</span>
                                    <span class="nav-link-subtitle">Campaign utama dan anggaran</span>
                                </span>
                            </span>
                            <span class="section-check" data-section-indicator="campaign">
                                <i class="bi bi-circle"></i>
                            </span>
                        </button>

                        <button class="nav-link" id="tab-ads-btn" data-bs-toggle="pill" data-bs-target="#tab-ads" type="button" role="tab">
                            <span class="nav-link-content">
                                <span class="nav-link-icon"><i class="bi bi-badge-ad-fill"></i></span>
                                <span class="nav-link-text">
                                    <span class="nav-link-title">Ads Running</span>
                                    <span class="nav-link-subtitle">Daftar ads yang berjalan</span>
                                </span>
                            </span>
                            <span class="section-check" data-section-indicator="ads">
                                <i class="bi bi-circle"></i>
                            </span>
                        </button>

                        <button class="nav-link" id="tab-events-btn" data-bs-toggle="pill" data-bs-target="#tab-events" type="button" role="tab">
                            <span class="nav-link-content">
                                <span class="nav-link-icon"><i class="bi bi-calendar-event-fill"></i></span>
                                <span class="nav-link-text">
                                    <span class="nav-link-title">Events</span>
                                    <span class="nav-link-subtitle">Agenda event dan aktivitas</span>
                                </span>
                            </span>
                            <span class="section-check" data-section-indicator="events">
                                <i class="bi bi-circle"></i>
                            </span>
                        </button>

                        <button class="nav-link" id="tab-snapshot-btn" data-bs-toggle="pill" data-bs-target="#tab-snapshot" type="button" role="tab">
                            <span class="nav-link-content">
                                <span class="nav-link-icon"><i class="bi bi-bar-chart-fill"></i></span>
                                <span class="nav-link-text">
                                    <span class="nav-link-title">Result Snapshot</span>
                                    <span class="nav-link-subtitle">Hasil utama periode ini</span>
                                </span>
                            </span>
                            <span class="section-check" data-section-indicator="snapshot">
                                <i class="bi bi-circle"></i>
                            </span>
                        </button>

                        <button class="nav-link" id="tab-insight-btn" data-bs-toggle="pill" data-bs-target="#tab-insight" type="button" role="tab">
                            <span class="nav-link-content">
                                <span class="nav-link-icon"><i class="bi bi-lightbulb-fill"></i></span>
                                <span class="nav-link-text">
                                    <span class="nav-link-title">Insight & Notes</span>
                                    <span class="nav-link-subtitle">Ringkasan dan catatan</span>
                                </span>
                            </span>
                            <span class="section-check" data-section-indicator="insight">
                                <i class="bi bi-circle"></i>
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-xl-9">
                <div class="tab-content" id="report-section-tabContent">

                    {{-- OVERVIEW --}}
                    <div class="tab-pane fade show active" id="tab-overview" role="tabpanel" aria-labelledby="tab-overview-btn">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header">
                                <div>
                                    <h5 class="content-card-title mb-1">Overview</h5>
                                    <p class="content-card-subtitle mb-0">
                                        Isi informasi utama report sebagai dasar untuk seluruh section lainnya.
                                    </p>
                                </div>
                                <div class="section-status-badge" data-section-badge="overview">
                                    <i class="bi bi-hourglass-split me-1"></i> Need Input
                                </div>
                            </div>

                            <div class="content-card-body">
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <label class="form-label">Title <span class="text-danger">*</span></label>
                                        <input type="text" name="title" class="form-control section-overview"
                                               value="{{ old('title', $report->title) }}"
                                               placeholder="Contoh: Marketing Report April 2026">
                                        <div class="invalid-feedback error-text" data-error-for="title"></div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label">Slug</label>
                                        <input type="text" name="slug" class="form-control"
                                               value="{{ old('slug', $report->slug) }}"
                                               placeholder="Akan digenerate otomatis jika kosong">
                                        <div class="invalid-feedback error-text" data-error-for="slug"></div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">Report No</label>
                                        <input type="text" name="report_no" id="reportNoField" class="form-control bg-light"
                                               value="{{ old('report_no', $report->report_no) }}"
                                               placeholder="Akan digenerate otomatis saat penyimpanan pertama"
                                               readonly>
                                        <div class="form-text">Nomor report dibuat otomatis oleh sistem.</div>
                                        <div class="invalid-feedback error-text" data-error-for="report_no"></div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">Period Type <span class="text-danger">*</span></label>
                                        <select name="period_type" class="form-select section-overview">
                                            @foreach ($periodTypeOptions as $value => $label)
                                                <option value="{{ $value }}" @selected(old('period_type', $report->period_type) === $value)>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback error-text" data-error-for="period_type"></div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <select name="status" id="statusField" class="form-select section-overview">
                                            @foreach ($statusOptions as $value => $label)
                                                <option value="{{ $value }}" @selected(old('status', $report->status) === $value)>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback error-text" data-error-for="status"></div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                        <input type="date" name="start_date" class="form-control section-overview"
                                               value="{{ old('start_date', optional($report->start_date)->format('Y-m-d') ?? $report->start_date) }}">
                                        <div class="invalid-feedback error-text" data-error-for="start_date"></div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                                        <input type="date" name="end_date" class="form-control section-overview"
                                               value="{{ old('end_date', optional($report->end_date)->format('Y-m-d') ?? $report->end_date) }}">
                                        <div class="invalid-feedback error-text" data-error-for="end_date"></div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">Active</label>
                                        <div class="form-switch-card">
                                            <div>
                                                <div class="form-switch-title">Report Active</div>
                                                <div class="form-switch-subtitle">Aktifkan report agar tetap muncul di daftar utama.</div>
                                            </div>
                                            <div class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1"
                                                       @checked(old('is_active', $report->is_active ?? true))>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- CAMPAIGNS --}}
                    <div class="tab-pane fade" id="tab-campaign" role="tabpanel" aria-labelledby="tab-campaign-btn">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header">
                                <div>
                                    <h5 class="content-card-title mb-1">Campaign Plan</h5>
                                    <p class="content-card-subtitle mb-0">
                                        Tambahkan campaign utama lengkap dengan objective, budget, PIC, dan status pelaksanaannya.
                                    </p>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="section-status-badge" data-section-badge="campaign">
                                        <i class="bi bi-dash-circle me-1"></i> Optional
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm" id="addCampaignBtn">
                                        <i class="bi bi-plus-circle me-1"></i> Add Campaign
                                    </button>
                                </div>
                            </div>

                            <div class="content-card-body">
                                <div id="campaignList" class="dynamic-section-list"></div>

                                <div id="campaignEmptyState" class="empty-state-box">
                                    <div class="empty-state-icon"><i class="bi bi-megaphone"></i></div>
                                    <div class="empty-state-title">Belum ada campaign</div>
                                    <div class="empty-state-subtitle">Boleh kosong jika periode ini memang tidak memakai campaign khusus.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ADS --}}
                    <div class="tab-pane fade" id="tab-ads" role="tabpanel" aria-labelledby="tab-ads-btn">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header">
                                <div>
                                    <h5 class="content-card-title mb-1">Ads Running</h5>
                                    <p class="content-card-subtitle mb-0">
                                        Catat iklan yang sedang berjalan pada masing-masing kanal beserta alokasi dan realisasi anggarannya.
                                    </p>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="section-status-badge" data-section-badge="ads">
                                        <i class="bi bi-dash-circle me-1"></i> Optional
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm" id="addAdBtn">
                                        <i class="bi bi-plus-circle me-1"></i> Add Ads
                                    </button>
                                </div>
                            </div>

                            <div class="content-card-body">
                                <div id="adsList" class="dynamic-section-list"></div>

                                <div id="adsEmptyState" class="empty-state-box">
                                    <div class="empty-state-icon"><i class="bi bi-badge-ad"></i></div>
                                    <div class="empty-state-title">Belum ada ads</div>
                                    <div class="empty-state-subtitle">Boleh kosong jika periode ini tidak menjalankan iklan berbayar.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- EVENTS --}}
                    <div class="tab-pane fade" id="tab-events" role="tabpanel" aria-labelledby="tab-events-btn">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header">
                                <div>
                                    <h5 class="content-card-title mb-1">Events</h5>
                                    <p class="content-card-subtitle mb-0">
                                        Tambahkan event yang relevan dengan aktivitas pemasaran pada periode report ini.
                                    </p>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="section-status-badge" data-section-badge="events">
                                        <i class="bi bi-dash-circle me-1"></i> Optional
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm" id="addEventBtn">
                                        <i class="bi bi-plus-circle me-1"></i> Add Event
                                    </button>
                                </div>
                            </div>

                            <div class="content-card-body">
                                <div id="eventsList" class="dynamic-section-list"></div>

                                <div id="eventsEmptyState" class="empty-state-box">
                                    <div class="empty-state-icon"><i class="bi bi-calendar-event"></i></div>
                                    <div class="empty-state-title">Belum ada event</div>
                                    <div class="empty-state-subtitle">Boleh kosong jika pada periode ini tidak ada event yang perlu dicatat.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SNAPSHOT --}}
                    <div class="tab-pane fade" id="tab-snapshot" role="tabpanel" aria-labelledby="tab-snapshot-btn">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header">
                                <div>
                                    <h5 class="content-card-title mb-1">Result Snapshot</h5>
                                    <p class="content-card-subtitle mb-0">
                                        Isi hasil utama report untuk memberikan gambaran ringkas mengenai capaian periode ini.
                                    </p>
                                </div>
                                <div class="section-status-badge" data-section-badge="snapshot">
                                    <i class="bi bi-hourglass-split me-1"></i> Need Input
                                </div>
                            </div>

                            <div class="content-card-body">
                                <div class="row g-3">
                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Total Leads</label>
                                        <input type="number" min="0" name="total_leads" class="form-control section-snapshot"
                                               value="{{ old('total_leads', $report->total_leads ?? 0) }}">
                                        <div class="invalid-feedback error-text" data-error-for="total_leads"></div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Total Registrants</label>
                                        <input type="number" min="0" name="total_registrants" class="form-control section-snapshot"
                                               value="{{ old('total_registrants', $report->total_registrants ?? 0) }}">
                                        <div class="invalid-feedback error-text" data-error-for="total_registrants"></div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Total Attendees</label>
                                        <input type="number" min="0" name="total_attendees" class="form-control section-snapshot"
                                               value="{{ old('total_attendees', $report->total_attendees ?? 0) }}">
                                        <div class="invalid-feedback error-text" data-error-for="total_attendees"></div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Total Conversions</label>
                                        <input type="number" min="0" name="total_conversions" class="form-control section-snapshot"
                                               value="{{ old('total_conversions', $report->total_conversions ?? 0) }}">
                                        <div class="invalid-feedback error-text" data-error-for="total_conversions"></div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Total Revenue</label>
                                        <input type="number" min="0" step="0.01" name="total_revenue" class="form-control section-snapshot"
                                               value="{{ old('total_revenue', $report->total_revenue ?? 0) }}">
                                        <div class="invalid-feedback error-text" data-error-for="total_revenue"></div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Current Total Budget</label>
                                        <input type="text" class="form-control bg-light" id="liveTotalBudget" value="{{ number_format($report->total_budget ?? 0, 2, '.', '') }}" readonly>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Current Actual Spend</label>
                                        <input type="text" class="form-control bg-light" id="liveActualSpend" value="{{ number_format($report->total_actual_spend ?? 0, 2, '.', '') }}" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- INSIGHT --}}
                    <div class="tab-pane fade" id="tab-insight" role="tabpanel" aria-labelledby="tab-insight-btn">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header">
                                <div>
                                    <h5 class="content-card-title mb-1">Insight & Notes</h5>
                                    <p class="content-card-subtitle mb-0">
                                        Lengkapi rangkuman, insight utama, tindak lanjut, dan catatan tambahan untuk report ini.
                                    </p>
                                </div>
                                <div class="section-status-badge" data-section-badge="insight">
                                    <i class="bi bi-dash-circle me-1"></i> Optional
                                </div>
                            </div>

                            <div class="content-card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Summary</label>
                                        <textarea name="summary" rows="4" class="form-control section-insight"
                                                  placeholder="Masukkan ringkasan umum aktivitas marketing pada periode ini">{{ old('summary', $report->summary) }}</textarea>
                                        <div class="invalid-feedback error-text" data-error-for="summary"></div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Key Insight</label>
                                        <textarea name="key_insight" rows="4" class="form-control section-insight"
                                                  placeholder="Masukkan insight utama dari pelaksanaan campaign, ads, atau event">{{ old('key_insight', $report->key_insight) }}</textarea>
                                        <div class="invalid-feedback error-text" data-error-for="key_insight"></div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Next Action</label>
                                        <textarea name="next_action" rows="4" class="form-control section-insight"
                                                  placeholder="Masukkan tindak lanjut yang direncanakan untuk periode berikutnya">{{ old('next_action', $report->next_action) }}</textarea>
                                        <div class="invalid-feedback error-text" data-error-for="next_action"></div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" rows="4" class="form-control section-insight"
                                                  placeholder="Masukkan catatan tambahan jika diperlukan">{{ old('notes', $report->notes) }}</textarea>
                                        <div class="invalid-feedback error-text" data-error-for="notes"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="content-card">
                    <div class="content-card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="footer-note">
                            <div class="footer-note-title">Final Check</div>
                            <div class="footer-note-subtitle">Pastikan komponen penting sudah siap sebelum menyimpan report.</div>
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('marketing.reports.index') }}" class="btn btn-light border">
                                Cancel
                            </a>
                            <button type="button" id="saveDraftBtnBottom" class="btn btn-save-draft">
                                <i class="bi bi-save me-1"></i> Save Draft
                            </button>
                            <button type="button" id="submitReportBtnBottom" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> {{ $isEdit ? 'Update Report' : 'Create Report' }}
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

{{-- TEMPLATES --}}
<template id="campaignRowTemplate">
    <div class="dynamic-item-card campaign-item-card" data-type="campaign">
        <div class="dynamic-item-header">
            <div>
                <div class="dynamic-item-title">Campaign Item</div>
                <div class="dynamic-item-subtitle">Isi informasi campaign secara lengkap.</div>
            </div>
            <button type="button" class="btn btn-sm btn-light border remove-item-btn">
                <i class="bi bi-trash3"></i>
            </button>
        </div>

        <div class="dynamic-item-body">
            <input type="hidden" class="field-id" data-field="id">
            <input type="hidden" class="field-temp-key" data-field="temp_key">

            <div class="row g-3">
                <div class="col-lg-6">
                    <label class="form-label">Campaign Name</label>
                    <input type="text" class="form-control field-input campaign-required" data-field="name" placeholder="Masukkan nama campaign">
                </div>

                <div class="col-lg-6">
                    <label class="form-label">Owner</label>
                    <input type="text" class="form-control field-input" data-field="owner_name" placeholder="Masukkan PIC / owner">
                </div>

                <div class="col-12">
                    <label class="form-label">Objective</label>
                    <textarea class="form-control field-input" rows="3" data-field="objective" placeholder="Masukkan objective campaign"></textarea>
                </div>

                <div class="col-lg-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control field-input" data-field="start_date">
                </div>

                <div class="col-lg-3">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control field-input" data-field="end_date">
                </div>

                <div class="col-lg-2">
                    <label class="form-label">Budget</label>
                    <input type="number" min="0" step="0.01" class="form-control field-input campaign-budget" data-field="budget" value="0">
                </div>

                <div class="col-lg-2">
                    <label class="form-label">Actual Spend</label>
                    <input type="number" min="0" step="0.01" class="form-control field-input campaign-actual-spend" data-field="actual_spend" value="0">
                </div>

                <div class="col-lg-2">
                    <label class="form-label">Status</label>
                    <select class="form-select field-input campaign-required" data-field="status">
                        @foreach ($campaignStatusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control field-input" rows="3" data-field="notes" placeholder="Masukkan catatan jika diperlukan"></textarea>
                </div>
            </div>
        </div>
    </div>
</template>

<template id="adRowTemplate">
    <div class="dynamic-item-card ads-item-card" data-type="ads">
        <div class="dynamic-item-header">
            <div>
                <div class="dynamic-item-title">Ads Item</div>
                <div class="dynamic-item-subtitle">Isi informasi ads dan hubungkan ke campaign jika diperlukan.</div>
            </div>
            <button type="button" class="btn btn-sm btn-light border remove-item-btn">
                <i class="bi bi-trash3"></i>
            </button>
        </div>

        <div class="dynamic-item-body">
            <input type="hidden" class="field-id" data-field="id">

            <div class="row g-3">
                <div class="col-lg-4">
                    <label class="form-label">Platform</label>
                    <input type="text" class="form-control field-input ads-required" data-field="platform" placeholder="Contoh: Meta Ads">
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Ads Name</label>
                    <input type="text" class="form-control field-input ads-required" data-field="ad_name" placeholder="Masukkan nama ads">
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Campaign Reference</label>
                    <select class="form-select field-input" data-field="campaign_ref">
                        <option value="">Select campaign</option>
                    </select>
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Objective</label>
                    <input type="text" class="form-control field-input" data-field="objective" placeholder="Contoh: Traffic / Lead Generation">
                </div>

                <div class="col-lg-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control field-input" data-field="start_date">
                </div>

                <div class="col-lg-2">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control field-input" data-field="end_date">
                </div>

                <div class="col-lg-2">
                    <label class="form-label">Budget</label>
                    <input type="number" min="0" step="0.01" class="form-control field-input ads-budget" data-field="budget" value="0">
                </div>

                <div class="col-lg-2">
                    <label class="form-label">Actual Spend</label>
                    <input type="number" min="0" step="0.01" class="form-control field-input ads-actual-spend" data-field="actual_spend" value="0">
                </div>

                <div class="col-lg-2">
                    <label class="form-label">Status</label>
                    <select class="form-select field-input ads-required" data-field="status">
                        @foreach ($adStatusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control field-input" rows="3" data-field="notes" placeholder="Masukkan catatan jika diperlukan"></textarea>
                </div>
            </div>
        </div>
    </div>
</template>

<template id="eventRowTemplate">
    <div class="dynamic-item-card event-item-card" data-type="events">
        <div class="dynamic-item-header">
            <div>
                <div class="dynamic-item-title">Event Item</div>
                <div class="dynamic-item-subtitle">Isi agenda event yang relevan dengan periode report ini.</div>
            </div>
            <button type="button" class="btn btn-sm btn-light border remove-item-btn">
                <i class="bi bi-trash3"></i>
            </button>
        </div>

        <div class="dynamic-item-body">
            <input type="hidden" class="field-id" data-field="id">

            <div class="row g-3">
                <div class="col-lg-5">
                    <label class="form-label">Event Name</label>
                    <input type="text" class="form-control field-input events-required" data-field="name" placeholder="Masukkan nama event">
                </div>

                <div class="col-lg-3">
                    <label class="form-label">Event Type</label>
                    <select class="form-select field-input events-required" data-field="event_type">
                        @foreach ($eventTypeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2">
                    <label class="form-label">Event Date</label>
                    <input type="date" class="form-control field-input" data-field="event_date">
                </div>

                <div class="col-lg-2">
                    <label class="form-label">Status</label>
                    <select class="form-select field-input events-required" data-field="status">
                        @foreach ($eventStatusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-5">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-control field-input" data-field="location" placeholder="Masukkan lokasi event">
                </div>

                <div class="col-lg-3">
                    <label class="form-label">Target Participants</label>
                    <input type="number" min="0" class="form-control field-input" data-field="target_participants" value="0">
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Budget</label>
                    <input type="number" min="0" step="0.01" class="form-control field-input event-budget" data-field="budget" value="0">
                </div>

                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control field-input" rows="3" data-field="notes" placeholder="Masukkan catatan jika diperlukan"></textarea>
                </div>
            </div>
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
    const form = document.getElementById('marketingReportForm');
    const campaignList = document.getElementById('campaignList');
    const adsList = document.getElementById('adsList');
    const eventsList = document.getElementById('eventsList');

    const addCampaignBtn = document.getElementById('addCampaignBtn');
    const addAdBtn = document.getElementById('addAdBtn');
    const addEventBtn = document.getElementById('addEventBtn');

    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const saveDraftBtnBottom = document.getElementById('saveDraftBtnBottom');
    const submitReportBtn = document.getElementById('submitReportBtn');
    const submitReportBtnBottom = document.getElementById('submitReportBtnBottom');

    const statusField = document.getElementById('statusField');
    const liveStatusText = document.getElementById('liveStatusText');

    const reportNoField = document.getElementById('reportNoField');

    const liveTotalBudget = document.getElementById('liveTotalBudget');
    const liveActualSpend = document.getElementById('liveActualSpend');

    const campaignEmptyState = document.getElementById('campaignEmptyState');
    const adsEmptyState = document.getElementById('adsEmptyState');
    const eventsEmptyState = document.getElementById('eventsEmptyState');

    const completedSectionCount = document.getElementById('completedSectionCount');
    const totalSectionCount = document.getElementById('totalSectionCount');
    const sectionProgressBar = document.getElementById('sectionProgressBar');

    const toastContainer = document.getElementById('toastContainer');

    const campaignRows = @json($campaignRows);
    const adRows = @json($adRows);
    const eventRows = @json($eventRows);

    let campaignIndex = 0;
    let adIndex = 0;
    let eventIndex = 0;
    let redirectTimeout = null;

    totalSectionCount.textContent = '6';

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
        const toast = new bootstrap.Toast(toastEl, {
            delay: 1600
        });

        toast.show();

        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    }

    function scheduleRedirect(url) {
        if (!url) return;

        if (redirectTimeout) {
            clearTimeout(redirectTimeout);
        }

        redirectTimeout = setTimeout(function () {
            window.location.href = url;
        }, 900);
    }

    function generateTempKey(prefix) {
        return `${prefix}_${Date.now()}_${Math.floor(Math.random() * 10000)}`;
    }

    function renderCampaignRow(data = {}) {
        const template = document.getElementById('campaignRowTemplate');
        const clone = template.content.cloneNode(true);
        const card = clone.querySelector('.dynamic-item-card');

        const rowIndex = campaignIndex++;
        const tempKey = data.temp_key || generateTempKey('cmp');

        card.dataset.index = rowIndex;
        card.dataset.tempKey = tempKey;

        setField(card, 'id', data.id ?? '');
        setField(card, 'temp_key', tempKey);
        setField(card, 'name', data.name ?? '');
        setField(card, 'owner_name', data.owner_name ?? '');
        setField(card, 'objective', data.objective ?? '');
        setField(card, 'start_date', normalizeDate(data.start_date));
        setField(card, 'end_date', normalizeDate(data.end_date));
        setField(card, 'budget', data.budget ?? 0);
        setField(card, 'actual_spend', data.actual_spend ?? 0);
        setField(card, 'status', data.status ?? 'planned');
        setField(card, 'notes', data.notes ?? '');

        applyCampaignNames(card, rowIndex);
        bindRemoveButton(card, campaignList, campaignEmptyState, refreshAll);
        bindInputs(card);

        campaignList.appendChild(card);
        toggleEmptyState(campaignList, campaignEmptyState);
        refreshCampaignReferences();
        refreshAll();
    }

    function renderAdRow(data = {}) {
        const template = document.getElementById('adRowTemplate');
        const clone = template.content.cloneNode(true);
        const card = clone.querySelector('.dynamic-item-card');

        const rowIndex = adIndex++;
        card.dataset.index = rowIndex;

        setField(card, 'id', data.id ?? '');
        setField(card, 'platform', data.platform ?? '');
        setField(card, 'ad_name', data.ad_name ?? '');
        setField(card, 'objective', data.objective ?? '');
        setField(card, 'start_date', normalizeDate(data.start_date));
        setField(card, 'end_date', normalizeDate(data.end_date));
        setField(card, 'budget', data.budget ?? 0);
        setField(card, 'actual_spend', data.actual_spend ?? 0);
        setField(card, 'status', data.status ?? 'active');
        setField(card, 'notes', data.notes ?? '');

        applyAdNames(card, rowIndex);
        bindRemoveButton(card, adsList, adsEmptyState, refreshAll);
        bindInputs(card);

        adsList.appendChild(card);

        refreshCampaignReferences();

        const campaignSelect = card.querySelector('[data-field="campaign_ref"]');
        const incomingCampaignId = data.marketing_report_campaign_id ?? data.campaign_id ?? '';
        const incomingCampaignTempKey = data.campaign_temp_key ?? '';

        if (incomingCampaignId) {
            campaignSelect.value = `id:${incomingCampaignId}`;
        } else if (incomingCampaignTempKey) {
            campaignSelect.value = `tmp:${incomingCampaignTempKey}`;
        }

        toggleEmptyState(adsList, adsEmptyState);
        refreshAll();
    }

    function renderEventRow(data = {}) {
        const template = document.getElementById('eventRowTemplate');
        const clone = template.content.cloneNode(true);
        const card = clone.querySelector('.dynamic-item-card');

        const rowIndex = eventIndex++;
        card.dataset.index = rowIndex;

        setField(card, 'id', data.id ?? '');
        setField(card, 'name', data.name ?? '');
        setField(card, 'event_type', data.event_type ?? 'owned_event');
        setField(card, 'event_date', normalizeDate(data.event_date));
        setField(card, 'location', data.location ?? '');
        setField(card, 'target_participants', data.target_participants ?? 0);
        setField(card, 'budget', data.budget ?? 0);
        setField(card, 'status', data.status ?? 'planned');
        setField(card, 'notes', data.notes ?? '');

        applyEventNames(card, rowIndex);
        bindRemoveButton(card, eventsList, eventsEmptyState, refreshAll);
        bindInputs(card);

        eventsList.appendChild(card);
        toggleEmptyState(eventsList, eventsEmptyState);
        refreshAll();
    }

    function setField(card, field, value) {
        const el = card.querySelector(`[data-field="${field}"]`);
        if (!el) return;
        el.value = value ?? '';
    }

    function applyCampaignNames(card, index) {
        const fields = card.querySelectorAll('.field-input, .field-id, .field-temp-key');
        fields.forEach((field) => {
            const name = field.dataset.field;
            if (!name) return;
            field.name = `campaigns[${index}][${name}]`;
        });
    }

    function applyAdNames(card, index) {
        const fields = card.querySelectorAll('.field-input, .field-id');
        fields.forEach((field) => {
            const fieldName = field.dataset.field;
            if (!fieldName) return;
            if (fieldName === 'campaign_ref') return;
            field.name = `ads[${index}][${fieldName}]`;
        });

        const campaignSelect = card.querySelector('[data-field="campaign_ref"]');
        campaignSelect.dataset.index = index;
    }

    function applyEventNames(card, index) {
        const fields = card.querySelectorAll('.field-input, .field-id');
        fields.forEach((field) => {
            const name = field.dataset.field;
            if (!name) return;
            field.name = `events[${index}][${name}]`;
        });
    }

    function bindRemoveButton(card, listEl, emptyEl, callback) {
        const btn = card.querySelector('.remove-item-btn');
        btn.addEventListener('click', function () {
            card.remove();
            toggleEmptyState(listEl, emptyEl);
            refreshCampaignReferences();
            callback();
        });
    }

    function bindInputs(card) {
        card.querySelectorAll('input, textarea, select').forEach((el) => {
            el.addEventListener('input', refreshAll);
            el.addEventListener('change', refreshAll);
        });
    }

    function toggleEmptyState(listEl, emptyEl) {
        emptyEl.style.display = listEl.children.length > 0 ? 'none' : 'block';
    }

    function normalizeDate(value) {
        if (!value) return '';
        if (typeof value === 'string' && value.length >= 10) {
            return value.substring(0, 10);
        }
        return value;
    }

    function getCampaignRefs() {
        return Array.from(campaignList.querySelectorAll('.campaign-item-card')).map((card) => {
            return {
                id: card.querySelector('[data-field="id"]').value || '',
                tempKey: card.querySelector('[data-field="temp_key"]').value || '',
                name: card.querySelector('[data-field="name"]').value || 'Unnamed Campaign',
            };
        });
    }

    function refreshCampaignReferences() {
        const refs = getCampaignRefs();

        adsList.querySelectorAll('[data-field="campaign_ref"]').forEach((select) => {
            const oldValue = select.value;
            select.innerHTML = '<option value="">Select campaign</option>';

            refs.forEach((ref) => {
                const option = document.createElement('option');
                option.value = ref.id ? `id:${ref.id}` : `tmp:${ref.tempKey}`;
                option.textContent = ref.name;
                select.appendChild(option);
            });

            const exists = Array.from(select.options).some(opt => opt.value === oldValue);
            if (exists) {
                select.value = oldValue;
            }
        });
    }

    function calculateLiveTotals() {
        let totalBudget = 0;
        let totalActualSpend = 0;

        campaignList.querySelectorAll('.campaign-budget').forEach((el) => {
            totalBudget += parseFloat(el.value || 0);
        });

        adsList.querySelectorAll('.ads-budget').forEach((el) => {
            totalBudget += parseFloat(el.value || 0);
        });

        eventsList.querySelectorAll('.event-budget').forEach((el) => {
            totalBudget += parseFloat(el.value || 0);
        });

        campaignList.querySelectorAll('.campaign-actual-spend').forEach((el) => {
            totalActualSpend += parseFloat(el.value || 0);
        });

        adsList.querySelectorAll('.ads-actual-spend').forEach((el) => {
            totalActualSpend += parseFloat(el.value || 0);
        });

        liveTotalBudget.value = totalBudget.toFixed(2);
        liveActualSpend.value = totalActualSpend.toFixed(2);
    }

    function isFilled(value) {
        return value !== null && value !== undefined && String(value).trim() !== '';
    }

    function getSectionStateRequired(isComplete) {
        return isComplete ? 'completed' : 'incomplete';
    }

    function getSectionStateOptional(itemsLength, isCompleteWhenHasData) {
        if (itemsLength === 0) return 'optional';
        return isCompleteWhenHasData ? 'completed' : 'incomplete';
    }

    function checkOverviewState() {
        const title = form.querySelector('[name="title"]').value;
        const periodType = form.querySelector('[name="period_type"]').value;
        const status = form.querySelector('[name="status"]').value;
        const startDate = form.querySelector('[name="start_date"]').value;
        const endDate = form.querySelector('[name="end_date"]').value;

        return getSectionStateRequired(
            isFilled(title) && isFilled(periodType) && isFilled(status) && isFilled(startDate) && isFilled(endDate)
        );
    }

    function checkCampaignState() {
        const items = campaignList.querySelectorAll('.campaign-item-card');
        const allValid = Array.from(items).every((item) => {
            const name = item.querySelector('[data-field="name"]').value;
            const status = item.querySelector('[data-field="status"]').value;
            return isFilled(name) && isFilled(status);
        });

        return getSectionStateOptional(items.length, allValid);
    }

    function checkAdsState() {
        const items = adsList.querySelectorAll('.ads-item-card');
        const allValid = Array.from(items).every((item) => {
            const platform = item.querySelector('[data-field="platform"]').value;
            const adName = item.querySelector('[data-field="ad_name"]').value;
            const status = item.querySelector('[data-field="status"]').value;
            return isFilled(platform) && isFilled(adName) && isFilled(status);
        });

        return getSectionStateOptional(items.length, allValid);
    }

    function checkEventsState() {
        const items = eventsList.querySelectorAll('.event-item-card');
        const allValid = Array.from(items).every((item) => {
            const name = item.querySelector('[data-field="name"]').value;
            const eventType = item.querySelector('[data-field="event_type"]').value;
            const status = item.querySelector('[data-field="status"]').value;
            return isFilled(name) && isFilled(eventType) && isFilled(status);
        });

        return getSectionStateOptional(items.length, allValid);
    }

    function checkSnapshotState() {
        const fields = [
            form.querySelector('[name="total_leads"]').value,
            form.querySelector('[name="total_registrants"]').value,
            form.querySelector('[name="total_attendees"]').value,
            form.querySelector('[name="total_conversions"]').value,
            form.querySelector('[name="total_revenue"]').value,
        ];

        return getSectionStateRequired(fields.every(value => value !== '' && value !== null));
    }

    function checkInsightState() {
        const summary = form.querySelector('[name="summary"]').value;
        const keyInsight = form.querySelector('[name="key_insight"]').value;
        const nextAction = form.querySelector('[name="next_action"]').value;
        const notes = form.querySelector('[name="notes"]').value;

        const hasContent = isFilled(summary) || isFilled(keyInsight) || isFilled(nextAction) || isFilled(notes);

        return hasContent ? 'completed' : 'optional';
    }

    function updateSectionUI(sectionKey, state) {
        const indicator = document.querySelector(`[data-section-indicator="${sectionKey}"]`);
        const badge = document.querySelector(`[data-section-badge="${sectionKey}"]`);

        if (indicator) {
            indicator.classList.remove('completed', 'optional');

            if (state === 'completed') {
                indicator.classList.add('completed');
                indicator.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
            } else if (state === 'optional') {
                indicator.classList.add('optional');
                indicator.innerHTML = '<i class="bi bi-dash-circle-fill"></i>';
            } else {
                indicator.innerHTML = '<i class="bi bi-circle"></i>';
            }
        }

        if (badge) {
            badge.classList.remove('completed', 'optional');

            if (state === 'completed') {
                badge.classList.add('completed');
                badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Ready';
            } else if (state === 'optional') {
                badge.classList.add('optional');
                badge.innerHTML = '<i class="bi bi-dash-circle me-1"></i> Optional';
            } else {
                badge.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Need Input';
            }
        }
    }

    function refreshProgress() {
        const sections = {
            overview: checkOverviewState(),
            campaign: checkCampaignState(),
            ads: checkAdsState(),
            events: checkEventsState(),
            snapshot: checkSnapshotState(),
            insight: checkInsightState(),
        };

        const filledCount = Object.values(sections).filter((state) => state === 'completed').length;
        const totalCount = Object.keys(sections).length;
        const percent = totalCount > 0 ? Math.round((filledCount / totalCount) * 100) : 0;

        Object.entries(sections).forEach(([key, state]) => {
            updateSectionUI(key, state);
        });

        completedSectionCount.textContent = String(filledCount);
        totalSectionCount.textContent = String(totalCount);
        sectionProgressBar.style.width = `${percent}%`;
    }

    function refreshAll() {
        calculateLiveTotals();
        refreshProgress();

        if (statusField && liveStatusText) {
            liveStatusText.textContent = statusField.options[statusField.selectedIndex]?.text || 'Draft';
        }

        if (reportNoField && !reportNoField.value) {
            reportNoField.value = '';
        }
    }

    function clearValidationErrors() {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.error-text').forEach(el => el.textContent = '');
    }

    function applyValidationErrors(errors) {
        Object.entries(errors).forEach(([key, messages]) => {
            const message = Array.isArray(messages) ? messages[0] : messages;

            const field = form.querySelector(`[name="${cssEscapeName(key)}"]`);
            if (field) {
                field.classList.add('is-invalid');
            }

            const errorHolder = form.querySelector(`[data-error-for="${key}"]`);
            if (errorHolder) {
                errorHolder.textContent = message;
            }
        });
    }

    function cssEscapeName(name) {
        return name.replaceAll('[', '\\[').replaceAll(']', '\\]');
    }

    function buildFormData() {
        refreshCampaignReferences();

        const fd = new FormData(form);

        adsList.querySelectorAll('.ads-item-card').forEach((card, index) => {
            const campaignRef = card.querySelector('[data-field="campaign_ref"]').value || '';

            fd.delete(`ads[${index}][campaign_id]`);
            fd.delete(`ads[${index}][campaign_temp_key]`);

            if (campaignRef.startsWith('id:')) {
                fd.append(`ads[${index}][campaign_id]`, campaignRef.replace('id:', ''));
            } else if (campaignRef.startsWith('tmp:')) {
                fd.append(`ads[${index}][campaign_temp_key]`, campaignRef.replace('tmp:', ''));
            }
        });

        return fd;
    }

    async function parseResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
            return await response.json();
        }

        const text = await response.text();

        return {
            success: false,
            message: text || 'Unexpected server response.',
        };
    }

    async function submitForm(forceDraft = false) {
        clearValidationErrors();

        if (forceDraft && statusField) {
            statusField.value = 'draft';
            liveStatusText.textContent = 'Draft';
        }

        const formData = buildFormData();

        const isEdit = '{{ $isEdit ? '1' : '0' }}' === '1';
        if (isEdit) {
            formData.append('_method', 'PUT');
        }

        const submitButtons = [saveDraftBtn, saveDraftBtnBottom, submitReportBtn, submitReportBtnBottom];
        submitButtons.forEach(btn => btn && (btn.disabled = true));

        const activeButton = forceDraft ? [saveDraftBtn, saveDraftBtnBottom] : [submitReportBtn, submitReportBtnBottom];
        activeButton.forEach((btn) => {
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

                showToast(data.message || 'Terjadi kesalahan saat menyimpan data.', 'danger');
                return;
            }

            if (data.report_no && reportNoField && !reportNoField.value) {
                reportNoField.value = data.report_no;
            }

            showToast(
                data.message || (forceDraft ? 'Draft berhasil disimpan.' : 'Report berhasil disimpan.'),
                'success'
            );

            if (data.redirect) {
                scheduleRedirect(data.redirect);
            }
        } catch (error) {
            showToast('Terjadi kesalahan saat mengirim data.', 'danger');
        } finally {
            submitButtons.forEach(btn => btn && (btn.disabled = false));

            activeButton.forEach((btn) => {
                if (btn && btn.dataset.originalHtml) {
                    btn.innerHTML = btn.dataset.originalHtml;
                }
            });
        }
    }

    addCampaignBtn.addEventListener('click', () => renderCampaignRow());
    addAdBtn.addEventListener('click', () => renderAdRow());
    addEventBtn.addEventListener('click', () => renderEventRow());

    saveDraftBtn.addEventListener('click', () => submitForm(true));
    saveDraftBtnBottom.addEventListener('click', () => submitForm(true));
    submitReportBtn.addEventListener('click', () => submitForm(false));
    submitReportBtnBottom.addEventListener('click', () => submitForm(false));

    form.querySelectorAll('input, textarea, select').forEach((el) => {
        el.addEventListener('input', refreshAll);
        el.addEventListener('change', refreshAll);
    });

    campaignRows.forEach(row => renderCampaignRow(row));
    adRows.forEach(row => renderAdRow(row));
    eventRows.forEach(row => renderEventRow(row));

    toggleEmptyState(campaignList, campaignEmptyState);
    toggleEmptyState(adsList, adsEmptyState);
    toggleEmptyState(eventsList, eventsEmptyState);

    refreshCampaignReferences();
    refreshAll();
});
</script>
@endpush