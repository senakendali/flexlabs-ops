@extends('layouts.app-dashboard')

@section('title', $material->exists ? 'Edit Trial & Workshop Material' : 'Create Trial & Workshop Material')

@section('content')
@php
    $isEdit = $material->exists;

    $formAction = $isEdit
        ? route('public-learning-materials.update', $material)
        : route('public-learning-materials.store');

    $formatDate = function ($value) {
        if (blank($value)) {
            return '';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return '';
        }
    };

    $formatDateTimeLocal = function ($value) {
        if (blank($value)) {
            return '';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d\TH:i');
        } catch (\Throwable $e) {
            return '';
        }
    };

    $status = old('status', $material->status ?? 'draft');

    $statusClass = match ($status) {
        'published' => 'status-published',
        'archived' => 'status-closed',
        default => 'status-draft',
    };

    $publicUrl = $isEdit
        ? route('public-learning-materials.show', [
            'token' => $material->public_token,
            'slug' => $material->slug,
        ])
        : null;
@endphp

<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic / Trial & Workshop Materials</div>

                <h1 class="page-title mb-2">
                    {{ $isEdit ? 'Edit Material' : 'Create Material' }}
                </h1>

                <p class="page-subtitle mb-0">
                    Kelola materi public untuk trial class dan workshop. Materi ini tidak masuk LMS,
                    tapi bisa diakses lewat public link dengan expired time.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('public-learning-materials.index') }}" class="btn btn-light border btn-modern">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>

                @if($isEdit && $publicUrl)
                    <a href="{{ $publicUrl }}" target="_blank" class="btn btn-light border btn-modern">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Open Public Page
                    </a>

                    <button
                        type="button"
                        class="btn btn-light border btn-modern"
                        data-copy-public-link="{{ $publicUrl }}"
                    >
                        <i class="bi bi-copy me-1"></i> Copy Link
                    </button>
                @endif

                <button type="button" id="saveDraftBtn" class="btn btn-light btn-modern">
                    <i class="bi bi-save me-1"></i> Save Draft
                </button>

                <button type="button" id="submitMaterialBtn" class="btn btn-light btn-modern">
                    <i class="bi bi-check-circle me-1"></i>
                    {{ $isEdit ? 'Update Material' : 'Create Material' }}
                </button>
            </div>
        </div>
    </div>

    <div id="formAlert" class="alert alert-danger alert-dismissible fade d-none" role="alert">
        <span id="formAlertMessage">Terjadi kesalahan.</span>
        <button type="button" class="btn-close" aria-label="Close" data-hide-form-alert></button>
    </div>

    <form
        id="publicLearningMaterialForm"
        method="POST"
        action="{{ $formAction }}"
        enctype="multipart/form-data"
    >
        @csrf

        @if($isEdit)
            @method('PUT')
        @endif

        <input type="hidden" id="isEditField" value="{{ $isEdit ? '1' : '0' }}">

        <div class="row g-4">
            <div class="col-xl-8">

                <div class="content-card section-card mb-4">
                    <div class="content-card-header section-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Material Information</h5>
                            <p class="content-card-subtitle mb-0">
                                Informasi utama yang akan tampil di landing page public.
                            </p>
                        </div>

                        <div class="section-status-badge" data-section-badge="information">
                            <i class="bi bi-hourglass-split me-1"></i> Need Input
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Type</label>
                                <select name="type" id="typeField" class="form-select section-watch" required>
                                    <option value="trial" {{ old('type', $material->type ?? 'trial') === 'trial' ? 'selected' : '' }}>
                                        Trial
                                    </option>
                                    <option value="workshop" {{ old('type', $material->type ?? 'trial') === 'workshop' ? 'selected' : '' }}>
                                        Workshop
                                    </option>
                                </select>
                                <div class="invalid-feedback error-text" data-error-for="type"></div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" id="statusField" class="form-select section-watch" required>
                                    <option value="draft" {{ old('status', $material->status ?? 'draft') === 'draft' ? 'selected' : '' }}>
                                        Draft
                                    </option>
                                    <option value="published" {{ old('status', $material->status ?? 'draft') === 'published' ? 'selected' : '' }}>
                                        Published
                                    </option>
                                    <option value="archived" {{ old('status', $material->status ?? 'draft') === 'archived' ? 'selected' : '' }}>
                                        Archived
                                    </option>
                                </select>
                                <div class="invalid-feedback error-text" data-error-for="status"></div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Event Date</label>
                                <input
                                    type="date"
                                    name="event_date"
                                    id="eventDateField"
                                    class="form-control section-watch"
                                    value="{{ old('event_date', $formatDate($material->event_date ?? null)) }}"
                                >
                                <div class="invalid-feedback error-text" data-error-for="event_date"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Title</label>
                                <input
                                    type="text"
                                    name="title"
                                    id="materialTitleInput"
                                    class="form-control section-watch"
                                    value="{{ old('title', $material->title) }}"
                                    placeholder="Contoh: HTML Structure Practice - Trial Class"
                                    required
                                >
                                <div class="invalid-feedback error-text" data-error-for="title"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Slug</label>
                                <input
                                    type="text"
                                    name="slug"
                                    id="materialSlugInput"
                                    class="form-control"
                                    value="{{ old('slug', $material->slug) }}"
                                    placeholder="Akan otomatis mengikuti title jika dikosongkan"
                                >
                                <div class="form-text">
                                    Dipakai untuk URL public. Kalau kosong, sistem akan generate dari title.
                                </div>
                                <div class="invalid-feedback error-text" data-error-for="slug"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Subtitle</label>
                                <input
                                    type="text"
                                    name="subtitle"
                                    class="form-control"
                                    value="{{ old('subtitle', $material->subtitle) }}"
                                    placeholder="Contoh: Praktik membuat struktur HTML pertama untuk Pioneers"
                                >
                                <div class="invalid-feedback error-text" data-error-for="subtitle"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea
                                    name="description"
                                    rows="5"
                                    class="form-control"
                                    placeholder="Deskripsi singkat materi, tujuan sesi, dan instruksi umum..."
                                >{{ old('description', $material->description) }}</textarea>
                                <div class="invalid-feedback error-text" data-error-for="description"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Instructor Name</label>
                                <input
                                    type="text"
                                    name="instructor_name"
                                    class="form-control"
                                    value="{{ old('instructor_name', $material->instructor_name) }}"
                                    placeholder="Nama instructor"
                                >
                                <div class="invalid-feedback error-text" data-error-for="instructor_name"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Location</label>
                                <input
                                    type="text"
                                    name="location"
                                    class="form-control"
                                    value="{{ old('location', $material->location) }}"
                                    placeholder="Online / Flexlabs BSD / Google Meet"
                                >
                                <div class="invalid-feedback error-text" data-error-for="location"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card section-card mb-4">
                    <div class="content-card-header section-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Schedule & Access</h5>
                            <p class="content-card-subtitle mb-0">
                                Atur waktu event dan batas akses public link.
                            </p>
                        </div>

                        <div class="section-status-badge" data-section-badge="schedule">
                            <i class="bi bi-hourglass-split me-1"></i> Need Input
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Start Time</label>
                                <input
                                    type="datetime-local"
                                    name="starts_at"
                                    id="startsAtField"
                                    class="form-control schedule-watch"
                                    value="{{ old('starts_at', $formatDateTimeLocal($material->starts_at ?? null)) }}"
                                >
                                <div class="form-text">
                                    Contoh: trial mulai jam 09:00.
                                </div>
                                <div class="invalid-feedback error-text" data-error-for="starts_at"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">End Time</label>
                                <input
                                    type="datetime-local"
                                    name="ends_at"
                                    id="endsAtInput"
                                    class="form-control schedule-watch"
                                    value="{{ old('ends_at', $formatDateTimeLocal($material->ends_at ?? null)) }}"
                                >
                                <div class="form-text">
                                    Contoh: trial selesai jam 12:00.
                                </div>
                                <div class="invalid-feedback error-text" data-error-for="ends_at"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Access Starts At</label>
                                <input
                                    type="datetime-local"
                                    name="access_starts_at"
                                    id="accessStartsAtField"
                                    class="form-control schedule-watch"
                                    value="{{ old('access_starts_at', $formatDateTimeLocal($material->access_starts_at ?? null)) }}"
                                >
                                <div class="form-text">
                                    Kalau kosong, sistem akan ikut Start Time.
                                </div>
                                <div class="invalid-feedback error-text" data-error-for="access_starts_at"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Access Ends At</label>
                                <input
                                    type="datetime-local"
                                    name="access_ends_at"
                                    id="accessEndsAtInput"
                                    class="form-control schedule-watch"
                                    value="{{ old('access_ends_at', $formatDateTimeLocal($material->access_ends_at ?? null)) }}"
                                >
                                <div class="form-text">
                                    Kalau kosong, sistem akan otomatis set End Time + 1 jam.
                                </div>
                                <div class="invalid-feedback error-text" data-error-for="access_ends_at"></div>
                            </div>
                        </div>

                        <div class="alert alert-info mb-0 mt-3">
                            <i class="bi bi-info-circle me-2"></i>
                            Contoh: trial jam 09:00 - 12:00, akses bisa dibuka sampai jam 13:00.
                            Setelah itu public link akan diblock.
                        </div>
                    </div>
                </div>

                <div class="content-card section-card mb-4">
                    <div class="content-card-header section-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Cover Image</h5>
                            <p class="content-card-subtitle mb-0">
                                Optional. Cover image akan tampil di halaman public material.
                            </p>
                        </div>

                        <div class="section-status-badge" data-section-badge="cover">
                            <i class="bi bi-image me-1"></i> Optional
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="row g-3 align-items-start">
                            @if($isEdit && $material->cover_image_path)
                                <div class="col-md-4">
                                    <label class="form-label">Current Cover</label>
                                    <div class="border rounded p-2 bg-light">
                                        <img
                                            src="{{ asset('storage/' . $material->cover_image_path) }}"
                                            alt="{{ $material->title }}"
                                            class="img-fluid rounded"
                                        >
                                    </div>
                                </div>
                            @endif

                            <div class="{{ $isEdit && $material->cover_image_path ? 'col-md-8' : 'col-12' }}">
                                <label class="form-label">
                                    {{ $isEdit && $material->cover_image_path ? 'Replace Cover Image' : 'Upload Cover Image' }}
                                </label>

                                <input
                                    type="file"
                                    name="cover_image"
                                    class="form-control"
                                    accept="image/*"
                                >

                                <div class="form-text">
                                    JPG, PNG, atau WebP. Maksimal 4MB.
                                </div>
                                <div class="invalid-feedback error-text" data-error-for="cover_image"></div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($isEdit)
                    <div class="content-card section-card mb-4">
                        <div class="content-card-header section-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Content Blocks</h5>
                                <p class="content-card-subtitle mb-0">
                                    Susun materi public dalam bentuk heading, text, code snippet, image, note, dan task.
                                </p>
                            </div>

                            <button
                                type="button"
                                class="btn btn-primary btn-modern"
                                data-bs-toggle="modal"
                                data-bs-target="#materialBlockModal"
                                data-mode="create"
                            >
                                <i class="bi bi-plus-circle me-1"></i> Add Block
                            </button>
                        </div>

                        <div class="content-card-body">
                            <div id="materialBlocksContainer">
                                @if($material->blocks->count())
                                    <div class="table-responsive dropdown-safe-table">
                                        <table class="table table-hover align-middle admin-table mb-0">
                                            <thead>
                                                <tr>
                                                    <th class="text-nowrap">Order</th>
                                                    <th>Block</th>
                                                    <th class="text-nowrap">Type</th>
                                                    <th class="text-nowrap">Status</th>
                                                    <th class="text-end text-nowrap">Action</th>
                                                </tr>
                                            </thead>

                                            <tbody id="materialBlocksTableBody">
                                                @foreach($material->blocks as $block)
                                                    <tr id="blockRow{{ $block->id }}">
                                                        <td class="text-nowrap">
                                                            <span class="fw-semibold">{{ $block->sort_order }}</span>
                                                        </td>

                                                        <td>
                                                            <div class="fw-semibold text-dark">
                                                                {{ $block->title ?: ucfirst($block->type) . ' Block' }}
                                                            </div>

                                                            <div class="text-muted small text-truncate" style="max-width: 520px;">
                                                                @if($block->type === 'code')
                                                                    {{ $block->code_language ? strtoupper($block->code_language) . ' · ' : '' }}
                                                                    {{ \Illuminate\Support\Str::limit($block->code_content, 90) }}
                                                                @elseif($block->type === 'image')
                                                                    {{ $block->image_caption ?: ($block->image_path ? 'Image uploaded' : 'No image') }}
                                                                @else
                                                                    {{ \Illuminate\Support\Str::limit($block->content, 110) }}
                                                                @endif
                                                            </div>
                                                        </td>

                                                        <td class="text-nowrap">
                                                            <span class="assignment-status-badge status-open">
                                                                {{ ucfirst($block->type) }}
                                                            </span>
                                                        </td>

                                                        <td class="text-nowrap">
                                                            @if($block->is_active)
                                                                <span class="assignment-status-badge status-published">Active</span>
                                                            @else
                                                                <span class="assignment-status-badge status-closed">Inactive</span>
                                                            @endif
                                                        </td>

                                                        <td class="text-end text-nowrap">
                                                            <div class="dropdown">
                                                                <button
                                                                    class="btn btn-sm btn-outline-secondary dropdown-toggle px-3"
                                                                    type="button"
                                                                    data-bs-toggle="dropdown"
                                                                    data-bs-boundary="viewport"
                                                                    aria-expanded="false"
                                                                >
                                                                    Actions
                                                                </button>

                                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                                    <li>
                                                                        <button
                                                                            type="button"
                                                                            class="dropdown-item"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#materialBlockModal"
                                                                            data-mode="edit"
                                                                            data-id="{{ $block->id }}"
                                                                            data-type="{{ $block->type }}"
                                                                            data-title-base64="{{ base64_encode($block->title ?? '') }}"
                                                                            data-content-base64="{{ base64_encode($block->content ?? '') }}"
                                                                            data-code-language="{{ $block->code_language }}"
                                                                            data-code-content-base64="{{ base64_encode($block->code_content ?? '') }}"
                                                                            data-image-caption-base64="{{ base64_encode($block->image_caption ?? '') }}"
                                                                            data-sort-order="{{ $block->sort_order }}"
                                                                            data-is-active="{{ $block->is_active ? '1' : '0' }}"
                                                                            data-update-url="{{ route('public-learning-materials.blocks.update', $block) }}"
                                                                        >
                                                                            <i class="bi bi-pencil-square me-2"></i>Edit
                                                                        </button>
                                                                    </li>

                                                                    <li>
                                                                        <button
                                                                            type="button"
                                                                            class="dropdown-item text-danger"
                                                                            data-delete-block
                                                                            data-delete-url="{{ route('public-learning-materials.blocks.destroy', $block) }}"
                                                                            data-delete-row="#blockRow{{ $block->id }}"
                                                                        >
                                                                            <i class="bi bi-trash me-2"></i>Delete
                                                                        </button>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div id="emptyBlockState" class="empty-state-box">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-layout-text-window-reverse"></i>
                                        </div>

                                        <h5 class="empty-state-title">Belum ada content block</h5>
                                        <p class="empty-state-text mb-0">
                                            Tambahkan block untuk menyusun materi public yang akan dibaca student.
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <div class="content-card">
                    <div class="content-card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="footer-note">
                            <div class="footer-note-title">Final Check</div>
                            <div class="footer-note-subtitle">
                                Pastikan title, jadwal, status, dan access window sudah benar sebelum dibagikan ke student.
                            </div>
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('public-learning-materials.index') }}" class="btn btn-light btn-modern">
                                Cancel
                            </a>

                            <button type="button" id="saveDraftBtnBottom" class="btn btn-light btn-modern">
                                <i class="bi bi-save me-1"></i> Save Draft
                            </button>

                            <button type="button" id="submitMaterialBtnBottom" class="btn btn-primary btn-modern">
                                <i class="bi bi-check-circle me-1"></i>
                                {{ $isEdit ? 'Update Material' : 'Create Material' }}
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-xl-4">
                <div class="content-card section-card mb-4">
                    <div class="content-card-header section-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Progress</h5>
                            <p class="content-card-subtitle mb-0">
                                Status kelengkapan form.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-semibold">
                                <span id="completedSectionCount">0</span>/<span id="totalSectionCount">2</span> sections completed
                            </div>
                            <div class="text-muted small" id="liveStatusText">
                                {{ ucfirst($status) }}
                            </div>
                        </div>

                        <div class="progress mb-3" role="progressbar" aria-label="Form progress">
                            <div id="sectionProgressBar" class="progress-bar" style="width: 0%"></div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Status</span>
                            <span id="liveStatusBadge" class="assignment-status-badge {{ $statusClass }}">
                                {{ ucfirst($status) }}
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Type</span>
                            <span class="fw-semibold" id="liveTypeText">
                                {{ ucfirst(old('type', $material->type ?? 'trial')) }}
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Blocks</span>
                            <span class="fw-semibold" id="blockCountText">
                                {{ $isEdit ? $material->blocks->count() : 0 }}
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Gallery Images</span>
                            <span class="fw-semibold">
                                {{ $isEdit ? $material->images->count() : 0 }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="content-card section-card mb-4">
                    <div class="content-card-header section-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Public Link</h5>
                            <p class="content-card-subtitle mb-0">
                                Link yang akan dibagikan ke student.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        @if($isEdit && $publicUrl)
                            <label class="form-label">URL</label>

                            <div class="input-group">
                                <input
                                    type="text"
                                    class="form-control"
                                    value="{{ $publicUrl }}"
                                    readonly
                                >

                                <button
                                    type="button"
                                    class="btn btn-outline-primary"
                                    data-copy-public-link="{{ $publicUrl }}"
                                >
                                    <i class="bi bi-copy"></i>
                                </button>
                            </div>

                            <div class="form-text">
                                Link aktif hanya jika status published dan access window masih valid.
                            </div>
                        @else
                            <div class="alert alert-warning mb-0">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Public link akan tersedia setelah material disimpan.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="content-card section-card">
                    <div class="content-card-header section-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Save</h5>
                            <p class="content-card-subtitle mb-0">
                                Simpan perubahan material.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('public-learning-materials.index') }}" class="btn btn-light btn-modern">
                                Cancel
                            </a>
                            <button type="button" id="saveDraftBtnSide" class="btn btn-light btn-modern">
                                <i class="bi bi-save me-1"></i> Save Draft
                            </button>

                            <button type="button" id="submitMaterialBtnSide" class="btn btn-primary btn-modern">
                                <i class="bi bi-check-circle me-1"></i>
                                {{ $isEdit ? 'Update Material' : 'Create Material' }}
                            </button>   
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @if($isEdit)
        <div class="modal fade" id="materialBlockModal" tabindex="-1" aria-labelledby="materialBlockModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content custom-modal">
                    <form
                        id="materialBlockForm"
                        data-store-url="{{ route('public-learning-materials.blocks.store', $material) }}"
                    >
                        @csrf

                        <input type="hidden" name="_method" value="POST">
                        <input type="hidden" name="id" value="">
                        <input type="hidden" id="blockUpdateUrl" value="">

                        <div class="modal-header border-0 pb-0">
                            <div>
                                <h5 class="modal-title" id="materialBlockModalLabel">Add Content Block</h5>
                                <p class="text-muted mb-0" id="materialBlockModalSubtitle">
                                    Tambahkan block materi untuk landing page public.
                                </p>
                            </div>

                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body pt-4">
                            <div class="alert alert-danger d-none form-alert" role="alert"></div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Block Type</label>
                                    <select name="type" id="blockTypeField" class="form-select" required>
                                        <option value="heading">Heading</option>
                                        <option value="text">Text</option>
                                        <option value="code">Code Snippet</option>
                                        <option value="image">Image</option>
                                        <option value="note">Note / Tips</option>
                                        <option value="task">Task / Practice</option>
                                    </select>
                                    <div class="invalid-feedback error-text" data-error-for="type"></div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Order</label>
                                    <input type="number" name="sort_order" class="form-control" min="1" value="1">
                                    <div class="invalid-feedback error-text" data-error-for="sort_order"></div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Status</label>
                                    <select name="is_active" class="form-select">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                    <div class="invalid-feedback error-text" data-error-for="is_active"></div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Title</label>
                                    <input
                                        type="text"
                                        name="title"
                                        class="form-control"
                                        placeholder="Contoh: Step 1 - Create HTML Structure"
                                    >
                                    <div class="invalid-feedback error-text" data-error-for="title"></div>
                                </div>

                                <div class="col-12" data-block-field="content">
                                    <label class="form-label">Content</label>
                                    <textarea
                                        name="content"
                                        rows="7"
                                        class="form-control"
                                        placeholder="Isi penjelasan materi, catatan, atau instruksi praktik..."
                                    ></textarea>
                                    <div class="invalid-feedback error-text" data-error-for="content"></div>
                                </div>

                                <div class="col-md-4 d-none" data-block-field="code">
                                    <label class="form-label">Code Language</label>
                                    <input
                                        type="text"
                                        name="code_language"
                                        class="form-control"
                                        placeholder="html / css / js / php"
                                    >
                                    <div class="invalid-feedback error-text" data-error-for="code_language"></div>
                                </div>

                                <div class="col-12 d-none" data-block-field="code">
                                    <label class="form-label">Code Content</label>
                                    <textarea
                                        name="code_content"
                                        rows="10"
                                        class="form-control"
                                        placeholder="Tempel potongan code yang bisa student copy paste..."
                                    ></textarea>
                                    <div class="invalid-feedback error-text" data-error-for="code_content"></div>
                                </div>

                                <div class="col-md-6 d-none" data-block-field="image">
                                    <label class="form-label">Image</label>
                                    <input
                                        type="file"
                                        name="image"
                                        class="form-control"
                                        accept="image/*"
                                    >
                                    <div class="form-text">
                                        Wajib diisi saat membuat image block baru. Saat edit boleh dikosongkan jika tidak ingin mengganti image.
                                    </div>
                                    <div class="invalid-feedback error-text" data-error-for="image"></div>
                                </div>

                                <div class="col-md-6 d-none" data-block-field="image">
                                    <label class="form-label">Image Caption</label>
                                    <input
                                        type="text"
                                        name="image_caption"
                                        class="form-control"
                                        placeholder="Caption image"
                                    >
                                    <div class="invalid-feedback error-text" data-error-for="image_caption"></div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                                Cancel
                            </button>

                            <button type="submit" class="btn btn-primary btn-modern submit-btn">
                                Save Block
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const form = document.getElementById('publicLearningMaterialForm');
    const isEditField = document.getElementById('isEditField');

    const titleInput = document.getElementById('materialTitleInput');
    const slugInput = document.getElementById('materialSlugInput');

    const typeField = document.getElementById('typeField');
    const statusField = document.getElementById('statusField');
    const eventDateField = document.getElementById('eventDateField');
    const startsAtField = document.getElementById('startsAtField');
    const endsAtInput = document.getElementById('endsAtInput');
    const accessEndsAtInput = document.getElementById('accessEndsAtInput');

    const liveStatusText = document.getElementById('liveStatusText');
    const liveStatusBadge = document.getElementById('liveStatusBadge');
    const liveTypeText = document.getElementById('liveTypeText');

    const completedSectionCount = document.getElementById('completedSectionCount');
    const totalSectionCount = document.getElementById('totalSectionCount');
    const sectionProgressBar = document.getElementById('sectionProgressBar');

    const formAlert = document.getElementById('formAlert');
    const formAlertMessage = document.getElementById('formAlertMessage');

    const saveDraftButtons = [
        document.getElementById('saveDraftBtn'),
        document.getElementById('saveDraftBtnBottom'),
        document.getElementById('saveDraftBtnSide'),
    ].filter(Boolean);

    const submitButtons = [
        document.getElementById('submitMaterialBtn'),
        document.getElementById('submitMaterialBtnBottom'),
        document.getElementById('submitMaterialBtnSide'),
    ].filter(Boolean);

    const allSubmitButtons = [...saveDraftButtons, ...submitButtons];

    if (!form) {
        return;
    }

    function slugify(value) {
        return String(value || '')
            .toLowerCase()
            .trim()
            .replace(/['"]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function encodeBase64Unicode(value) {
        const text = String(value ?? '');

        try {
            return btoa(unescape(encodeURIComponent(text)));
        } catch (error) {
            return '';
        }
    }

    function decodeBase64Unicode(value) {
        if (!value) {
            return '';
        }

        try {
            return decodeURIComponent(escape(atob(value)));
        } catch (error) {
            return '';
        }
    }

    function capitalize(value) {
        const text = String(value || '');

        if (!text) {
            return '';
        }

        return text.charAt(0).toUpperCase() + text.slice(1);
    }

    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('appToast');
        const toastBody = toastEl?.querySelector('.toast-body');

        if (!window.bootstrap || !toastEl || !toastBody) {
            return;
        }

        toastBody.textContent = message;

        toastEl.classList.remove('bg-success', 'bg-danger', 'text-white');

        if (type === 'success') {
            toastEl.classList.add('bg-success', 'text-white');
        } else {
            toastEl.classList.add('bg-danger', 'text-white');
        }

        bootstrap.Toast.getOrCreateInstance(toastEl, {
            delay: 2600,
        }).show();
    }

    function showFormAlert(message) {
        if (!formAlert || !formAlertMessage) {
            return;
        }

        formAlertMessage.textContent = message || 'Terjadi kesalahan.';
        formAlert.classList.remove('d-none');
        formAlert.classList.add('show');
    }

    function hideFormAlert() {
        if (!formAlert) {
            return;
        }

        formAlert.classList.add('d-none');
        formAlert.classList.remove('show');
    }

    function cssEscapeName(name) {
        return String(name || '')
            .replaceAll('[', '\\[')
            .replaceAll(']', '\\]');
    }

    function clearValidationErrors() {
        hideFormAlert();

        form.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });

        form.querySelectorAll('.error-text').forEach(function (el) {
            el.textContent = '';
        });
    }

    function applyValidationErrors(errors) {
        Object.entries(errors || {}).forEach(function ([key, messages]) {
            const message = Array.isArray(messages) ? messages[0] : messages;
            const field = form.querySelector(`[name="${cssEscapeName(key)}"]`);
            const errorHolder = form.querySelector(`[data-error-for="${key}"]`);

            if (field) {
                field.classList.add('is-invalid');
            }

            if (errorHolder) {
                errorHolder.textContent = message;
            }
        });
    }

    async function parseResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
            return await response.json();
        }

        const text = await response.text();

        return {
            success: false,
            message: text || 'Unexpected server response.'
        };
    }

    function setButtonsLoading(buttons, isLoading, loadingText = 'Saving...') {
        allSubmitButtons.forEach(function (btn) {
            btn.disabled = isLoading;
        });

        buttons.forEach(function (btn) {
            if (!btn) {
                return;
            }

            if (isLoading) {
                btn.dataset.originalHtml = btn.innerHTML;
                btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span>${loadingText}`;
                return;
            }

            if (btn.dataset.originalHtml) {
                btn.innerHTML = btn.dataset.originalHtml;
            }
        });
    }

    function getStatusClass(status) {
        if (status === 'published') {
            return 'status-published';
        }

        if (status === 'archived') {
            return 'status-closed';
        }

        return 'status-draft';
    }

    function updateStatusUI() {
        const status = statusField?.value || 'draft';
        const label = capitalize(status);

        if (liveStatusText) {
            liveStatusText.textContent = label;
        }

        if (liveStatusBadge) {
            liveStatusBadge.textContent = label;
            liveStatusBadge.classList.remove('status-published', 'status-closed', 'status-draft');
            liveStatusBadge.classList.add(getStatusClass(status));
        }
    }

    function updateTypeUI() {
        if (!liveTypeText || !typeField) {
            return;
        }

        liveTypeText.textContent = typeField.value === 'workshop' ? 'Workshop' : 'Trial';
    }

    function checkInformationState() {
        if (!titleInput?.value.trim()) {
            return 'need';
        }

        if (!typeField?.value) {
            return 'need';
        }

        if (!statusField?.value) {
            return 'need';
        }

        return 'completed';
    }

    function checkScheduleState() {
        if (!eventDateField?.value) {
            return 'need';
        }

        if (!startsAtField?.value || !endsAtInput?.value) {
            return 'need';
        }

        return 'completed';
    }

    function updateSectionUI(key, state) {
        const badge = document.querySelector(`[data-section-badge="${key}"]`);

        if (!badge) {
            return;
        }

        if (state === 'completed') {
            badge.innerHTML = '<i class="bi bi-check-circle me-1"></i> Completed';
            return;
        }

        badge.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Need Input';
    }

    function refreshProgress() {
        const sections = {
            information: checkInformationState(),
            schedule: checkScheduleState(),
        };

        const filledCount = Object.values(sections).filter(function (state) {
            return state === 'completed';
        }).length;

        const totalCount = Object.keys(sections).length;
        const percent = totalCount > 0 ? Math.round((filledCount / totalCount) * 100) : 0;

        Object.entries(sections).forEach(function ([key, state]) {
            updateSectionUI(key, state);
        });

        if (completedSectionCount) {
            completedSectionCount.textContent = String(filledCount);
        }

        if (totalSectionCount) {
            totalSectionCount.textContent = String(totalCount);
        }

        if (sectionProgressBar) {
            sectionProgressBar.style.width = `${percent}%`;
        }

        updateStatusUI();
        updateTypeUI();
    }

    function scheduleRedirect(url) {
        window.setTimeout(function () {
            window.location.href = url;
        }, 700);
    }

    async function submitForm(forceDraft = false) {
        clearValidationErrors();

        if (forceDraft && statusField) {
            statusField.value = 'draft';
            updateStatusUI();
        }

        const formData = new FormData(form);
        const isEdit = isEditField?.value === '1';

        if (isEdit) {
            formData.set('_method', 'PUT');
        }

        const activeButtons = forceDraft ? saveDraftButtons : submitButtons;

        setButtonsLoading(activeButtons, true, forceDraft ? 'Saving Draft...' : 'Saving...');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });

            const data = await parseResponse(response);

            if (!response.ok || data.success === false) {
                if (response.status === 422 && data.errors) {
                    applyValidationErrors(data.errors);
                }

                const message = data.message || 'Terjadi kesalahan saat menyimpan material.';

                showFormAlert(message);
                showToast(message, 'danger');
                return;
            }

            const message = data.message || (forceDraft ? 'Draft material berhasil disimpan.' : 'Material berhasil disimpan.');

            showToast(message, 'success');

            const redirectUrl = data.redirect_url || data.data?.redirect_url;

            if (redirectUrl) {
                scheduleRedirect(redirectUrl);
                return;
            }

            refreshProgress();
        } catch (error) {
            const message = 'Terjadi kesalahan saat mengirim data.';
            showFormAlert(message);
            showToast(message, 'danger');
        } finally {
            setButtonsLoading(activeButtons, false);
        }
    }

    if (titleInput && slugInput) {
        let slugTouched = Boolean(slugInput.value);

        slugInput.addEventListener('input', function () {
            slugTouched = true;
        });

        titleInput.addEventListener('input', function () {
            if (slugTouched) {
                return;
            }

            slugInput.value = slugify(titleInput.value);
        });
    }

    if (endsAtInput && accessEndsAtInput) {
        endsAtInput.addEventListener('change', function () {
            if (accessEndsAtInput.value) {
                return;
            }

            const endDate = new Date(endsAtInput.value);

            if (Number.isNaN(endDate.getTime())) {
                return;
            }

            endDate.setHours(endDate.getHours() + 1);

            const year = endDate.getFullYear();
            const month = String(endDate.getMonth() + 1).padStart(2, '0');
            const day = String(endDate.getDate()).padStart(2, '0');
            const hour = String(endDate.getHours()).padStart(2, '0');
            const minute = String(endDate.getMinutes()).padStart(2, '0');

            accessEndsAtInput.value = `${year}-${month}-${day}T${hour}:${minute}`;

            refreshProgress();
        });
    }

    document.querySelectorAll('[data-copy-public-link]').forEach(function (button) {
        button.addEventListener('click', async function () {
            const value = button.getAttribute('data-copy-public-link') || '';

            if (!value) {
                return;
            }

            try {
                await navigator.clipboard.writeText(value);
                showToast('Public link berhasil dicopy.', 'success');
            } catch (error) {
                showToast('Gagal copy link. Silakan copy manual.', 'danger');
            }
        });
    });

    document.querySelectorAll('[data-hide-form-alert]').forEach(function (button) {
        button.addEventListener('click', hideFormAlert);
    });

    saveDraftButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            submitForm(true);
        });
    });

    submitButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            submitForm(false);
        });
    });

    form.querySelectorAll('input, textarea, select').forEach(function (input) {
        input.addEventListener('input', refreshProgress);
        input.addEventListener('change', refreshProgress);
    });

    const materialBlockModal = document.getElementById('materialBlockModal');
    const materialBlockForm = document.getElementById('materialBlockForm');
    const blockTypeField = document.getElementById('blockTypeField');
    const blockUpdateUrlField = document.getElementById('blockUpdateUrl');
    const blockCountText = document.getElementById('blockCountText');

    function clearBlockValidationErrors() {
        if (!materialBlockForm) {
            return;
        }

        const alertBox = materialBlockForm.querySelector('.form-alert');

        if (alertBox) {
            alertBox.classList.add('d-none');
            alertBox.innerHTML = '';
        }

        materialBlockForm.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });

        materialBlockForm.querySelectorAll('.error-text').forEach(function (el) {
            el.textContent = '';
        });
    }

    function showBlockErrors(errors) {
        if (!materialBlockForm) {
            return;
        }

        const alertBox = materialBlockForm.querySelector('.form-alert');
        const messages = [];

        Object.entries(errors || {}).forEach(function ([key, fieldErrors]) {
            const message = Array.isArray(fieldErrors) ? fieldErrors[0] : fieldErrors;
            const field = materialBlockForm.querySelector(`[name="${cssEscapeName(key)}"]`);
            const errorHolder = materialBlockForm.querySelector(`[data-error-for="${key}"]`);

            if (field) {
                field.classList.add('is-invalid');
            }

            if (errorHolder) {
                errorHolder.textContent = message;
            }

            if (message) {
                messages.push(`<div>${escapeHtml(message)}</div>`);
            }
        });

        if (alertBox && messages.length) {
            alertBox.innerHTML = messages.join('');
            alertBox.classList.remove('d-none');
        }
    }

    function syncBlockFields() {
        if (!blockTypeField || !materialBlockForm) {
            return;
        }

        const type = blockTypeField.value;

        materialBlockForm.querySelectorAll('[data-block-field]').forEach(function (fieldGroup) {
            fieldGroup.classList.add('d-none');
        });

        if (['heading', 'text', 'note', 'task'].includes(type)) {
            materialBlockForm.querySelectorAll('[data-block-field="content"]').forEach(function (fieldGroup) {
                fieldGroup.classList.remove('d-none');
            });
        }

        if (type === 'code') {
            materialBlockForm.querySelectorAll('[data-block-field="code"]').forEach(function (fieldGroup) {
                fieldGroup.classList.remove('d-none');
            });
        }

        if (type === 'image') {
            materialBlockForm.querySelectorAll('[data-block-field="image"]').forEach(function (fieldGroup) {
                fieldGroup.classList.remove('d-none');
            });
        }
    }

    function getNextBlockOrder() {
        const orders = Array.from(document.querySelectorAll('#materialBlocksTableBody tr td:first-child .fw-semibold'))
            .map(function (el) {
                return parseInt(el.textContent.trim(), 10);
            })
            .filter(function (value) {
                return Number.isFinite(value);
            });

        if (!orders.length) {
            return 1;
        }

        return Math.max(...orders) + 1;
    }

    function updateBlockCount() {
        if (!blockCountText) {
            return;
        }

        const rowCount = document.querySelectorAll('#materialBlocksTableBody tr').length;
        blockCountText.textContent = String(rowCount);
    }

    function resetBlockForm() {
        if (!materialBlockForm) {
            return;
        }

        materialBlockForm.reset();
        clearBlockValidationErrors();

        materialBlockForm.querySelector('input[name="_method"]').value = 'POST';
        materialBlockForm.querySelector('input[name="id"]').value = '';
        materialBlockForm.querySelector('select[name="type"]').value = 'heading';
        materialBlockForm.querySelector('select[name="is_active"]').value = '1';
        materialBlockForm.querySelector('input[name="sort_order"]').value = String(getNextBlockOrder());

        if (blockUpdateUrlField) {
            blockUpdateUrlField.value = '';
        }

        const submitBtn = materialBlockForm.querySelector('.submit-btn');

        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Save Block';
            submitBtn.dataset.defaultText = 'Save Block';
        }

        syncBlockFields();
    }

    function ensureBlockTable() {
        let tbody = document.getElementById('materialBlocksTableBody');

        if (tbody) {
            return tbody;
        }

        const container = document.getElementById('materialBlocksContainer');

        if (!container) {
            return null;
        }

        container.innerHTML = `
            <div class="table-responsive dropdown-safe-table">
                <table class="table table-hover align-middle admin-table mb-0">
                    <thead>
                        <tr>
                            <th class="text-nowrap">Order</th>
                            <th>Block</th>
                            <th class="text-nowrap">Type</th>
                            <th class="text-nowrap">Status</th>
                            <th class="text-end text-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody id="materialBlocksTableBody"></tbody>
                </table>
            </div>
        `;

        return document.getElementById('materialBlocksTableBody');
    }

    function buildBlockRow(block) {
        const title = block.title || `${capitalize(block.type)} Block`;

        let summary = '';

        if (block.type === 'code') {
            summary = `${block.code_language ? block.code_language.toUpperCase() + ' · ' : ''}${block.code_content || ''}`;
        } else if (block.type === 'image') {
            summary = block.image_caption || (block.image_path ? 'Image uploaded' : 'No image');
        } else {
            summary = block.content || '';
        }

        const statusBadge = block.is_active
            ? '<span class="assignment-status-badge status-published">Active</span>'
            : '<span class="assignment-status-badge status-closed">Inactive</span>';

        const updateUrl = `{{ route('public-learning-materials.blocks.update', ['block' => '__ID__']) }}`.replace('__ID__', block.id);
        const deleteUrl = `{{ route('public-learning-materials.blocks.destroy', ['block' => '__ID__']) }}`.replace('__ID__', block.id);

        return `
            <tr id="blockRow${escapeHtml(block.id)}">
                <td class="text-nowrap">
                    <span class="fw-semibold">${escapeHtml(block.sort_order)}</span>
                </td>

                <td>
                    <div class="fw-semibold text-dark">${escapeHtml(title)}</div>
                    <div class="text-muted small text-truncate" style="max-width: 520px;">
                        ${escapeHtml(summary).slice(0, 140)}
                    </div>
                </td>

                <td class="text-nowrap">
                    <span class="assignment-status-badge status-open">
                        ${escapeHtml(capitalize(block.type))}
                    </span>
                </td>

                <td class="text-nowrap">
                    ${statusBadge}
                </td>

                <td class="text-end text-nowrap">
                    <div class="dropdown">
                        <button
                            class="btn btn-sm btn-outline-secondary dropdown-toggle px-3"
                            type="button"
                            data-bs-toggle="dropdown"
                            data-bs-boundary="viewport"
                            aria-expanded="false"
                        >
                            Actions
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li>
                                <button
                                    type="button"
                                    class="dropdown-item"
                                    data-bs-toggle="modal"
                                    data-bs-target="#materialBlockModal"
                                    data-mode="edit"
                                    data-id="${escapeHtml(block.id)}"
                                    data-type="${escapeHtml(block.type)}"
                                    data-title-base64="${escapeHtml(encodeBase64Unicode(block.title || ''))}"
                                    data-content-base64="${escapeHtml(encodeBase64Unicode(block.content || ''))}"
                                    data-code-language="${escapeHtml(block.code_language || '')}"
                                    data-code-content-base64="${escapeHtml(encodeBase64Unicode(block.code_content || ''))}"
                                    data-image-caption-base64="${escapeHtml(encodeBase64Unicode(block.image_caption || ''))}"
                                    data-sort-order="${escapeHtml(block.sort_order || 1)}"
                                    data-is-active="${block.is_active ? '1' : '0'}"
                                    data-update-url="${escapeHtml(updateUrl)}"
                                >
                                    <i class="bi bi-pencil-square me-2"></i>Edit
                                </button>
                            </li>

                            <li>
                                <button
                                    type="button"
                                    class="dropdown-item text-danger"
                                    data-delete-block
                                    data-delete-url="${escapeHtml(deleteUrl)}"
                                    data-delete-row="#blockRow${escapeHtml(block.id)}"
                                >
                                    <i class="bi bi-trash me-2"></i>Delete
                                </button>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        `;
    }

    function upsertBlockRow(block) {
        const tbody = ensureBlockTable();

        if (!tbody) {
            return;
        }

        const existingRow = document.getElementById(`blockRow${block.id}`);
        const rowHtml = buildBlockRow(block);

        if (existingRow) {
            existingRow.outerHTML = rowHtml;
        } else {
            tbody.insertAdjacentHTML('beforeend', rowHtml);
        }

        updateBlockCount();
    }

    async function hideBlockModal() {
        if (!materialBlockModal || !window.bootstrap) {
            return;
        }

        const instance = bootstrap.Modal.getInstance(materialBlockModal) || bootstrap.Modal.getOrCreateInstance(materialBlockModal);

        await new Promise(function (resolve) {
            let resolved = false;

            function done() {
                if (resolved) {
                    return;
                }

                resolved = true;
                resolve();
            }

            materialBlockModal.addEventListener('hidden.bs.modal', done, { once: true });
            instance.hide();

            window.setTimeout(done, 360);
        });
    }

    async function submitBlockForm(event) {
        event.preventDefault();

        if (!materialBlockForm) {
            return;
        }

        clearBlockValidationErrors();

        const methodInput = materialBlockForm.querySelector('input[name="_method"]');
        const isUpdate = methodInput?.value === 'PUT';
        const actionUrl = isUpdate ? blockUpdateUrlField?.value : materialBlockForm.dataset.storeUrl;

        if (!actionUrl) {
            showToast('Route block belum tersedia.', 'danger');
            return;
        }

        const submitBtn = materialBlockForm.querySelector('.submit-btn');
        const originalHtml = submitBtn?.innerHTML || 'Save Block';

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
        }

        const formData = new FormData(materialBlockForm);

        if (isUpdate) {
            formData.set('_method', 'PUT');
        }

        try {
            const response = await fetch(actionUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });

            const data = await parseResponse(response);

            if (!response.ok || data.success === false) {
                if (response.status === 422 && data.errors) {
                    showBlockErrors(data.errors);
                }

                showToast(data.message || 'Gagal menyimpan block.', 'danger');
                return;
            }

            upsertBlockRow(data.data);

            await hideBlockModal();

            resetBlockForm();

            showToast(data.message || 'Block berhasil disimpan.', 'success');
        } catch (error) {
            showToast('Terjadi kesalahan saat menyimpan block.', 'danger');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHtml;
            }
        }
    }

    async function deleteBlock(button) {
        const deleteUrl = button.getAttribute('data-delete-url');
        const rowSelector = button.getAttribute('data-delete-row');

        if (!deleteUrl) {
            showToast('Route delete block belum tersedia.', 'danger');
            return;
        }

        if (!confirm('Yakin mau hapus block ini?')) {
            return;
        }

        button.disabled = true;

        try {
            const response = await fetch(deleteUrl, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            const data = await parseResponse(response);

            if (!response.ok || data.success === false) {
                showToast(data.message || 'Gagal menghapus block.', 'danger');
                button.disabled = false;
                return;
            }

            const row = rowSelector ? document.querySelector(rowSelector) : null;

            if (row) {
                row.remove();
            }

            const tbody = document.getElementById('materialBlocksTableBody');

            if (tbody && tbody.children.length === 0) {
                const container = document.getElementById('materialBlocksContainer');

                if (container) {
                    container.innerHTML = `
                        <div id="emptyBlockState" class="empty-state-box">
                            <div class="empty-state-icon">
                                <i class="bi bi-layout-text-window-reverse"></i>
                            </div>

                            <h5 class="empty-state-title">Belum ada content block</h5>
                            <p class="empty-state-text mb-0">
                                Tambahkan block untuk menyusun materi public yang akan dibaca student.
                            </p>
                        </div>
                    `;
                }
            }

            updateBlockCount();

            showToast(data.message || 'Block berhasil dihapus.', 'success');
        } catch (error) {
            showToast('Terjadi kesalahan saat menghapus block.', 'danger');
            button.disabled = false;
        }
    }

    if (blockTypeField) {
        blockTypeField.addEventListener('change', syncBlockFields);
    }

    if (materialBlockModal && materialBlockForm) {
        materialBlockModal.addEventListener('show.bs.modal', function (event) {
            resetBlockForm();

            const button = event.relatedTarget;
            const mode = button?.dataset?.mode || 'create';

            const modalTitle = document.getElementById('materialBlockModalLabel');
            const modalSubtitle = document.getElementById('materialBlockModalSubtitle');
            const submitBtn = materialBlockForm.querySelector('.submit-btn');

            if (mode === 'edit') {
                if (modalTitle) {
                    modalTitle.textContent = 'Edit Content Block';
                }

                if (modalSubtitle) {
                    modalSubtitle.textContent = 'Perbarui content block material public.';
                }

                if (submitBtn) {
                    submitBtn.innerHTML = 'Update Block';
                    submitBtn.dataset.defaultText = 'Update Block';
                }

                materialBlockForm.querySelector('input[name="_method"]').value = 'PUT';
                materialBlockForm.querySelector('input[name="id"]').value = button.dataset.id || '';
                materialBlockForm.querySelector('select[name="type"]').value = button.dataset.type || 'text';
                materialBlockForm.querySelector('input[name="title"]').value = decodeBase64Unicode(button.dataset.titleBase64 || '');
                materialBlockForm.querySelector('textarea[name="content"]').value = decodeBase64Unicode(button.dataset.contentBase64 || '');
                materialBlockForm.querySelector('input[name="code_language"]').value = button.dataset.codeLanguage || '';
                materialBlockForm.querySelector('textarea[name="code_content"]').value = decodeBase64Unicode(button.dataset.codeContentBase64 || '');
                materialBlockForm.querySelector('input[name="image_caption"]').value = decodeBase64Unicode(button.dataset.imageCaptionBase64 || '');
                materialBlockForm.querySelector('input[name="sort_order"]').value = button.dataset.sortOrder || 1;
                materialBlockForm.querySelector('select[name="is_active"]').value = button.dataset.isActive || '1';

                if (blockUpdateUrlField) {
                    blockUpdateUrlField.value = button.dataset.updateUrl || '';
                }
            } else {
                if (modalTitle) {
                    modalTitle.textContent = 'Add Content Block';
                }

                if (modalSubtitle) {
                    modalSubtitle.textContent = 'Tambahkan block materi untuk landing page public.';
                }
            }

            syncBlockFields();
        });

        materialBlockForm.addEventListener('submit', submitBlockForm);
    }

    document.addEventListener('click', function (event) {
        const deleteButton = event.target.closest('[data-delete-block]');

        if (deleteButton) {
            deleteBlock(deleteButton);
        }
    });

    refreshProgress();
    updateBlockCount();
});
</script>
@endpush