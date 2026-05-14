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

                <button type="submit" form="publicLearningMaterialForm" class="btn btn-primary btn-modern">
                    <i class="bi bi-save me-1"></i>
                    {{ $isEdit ? 'Update Material' : 'Create Material' }}
                </button>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="fw-bold mb-2">Ada input yang perlu dicek lagi:</div>

            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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

        <div class="row g-4">
            <div class="col-xl-8">

                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Material Information</h5>
                            <p class="content-card-subtitle mb-0">
                                Informasi utama yang akan tampil di landing page public.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-select" required>
                                    <option value="trial" {{ old('type', $material->type ?? 'trial') === 'trial' ? 'selected' : '' }}>
                                        Trial
                                    </option>
                                    <option value="workshop" {{ old('type', $material->type ?? 'trial') === 'workshop' ? 'selected' : '' }}>
                                        Workshop
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
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
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Event Date</label>
                                <input
                                    type="date"
                                    name="event_date"
                                    class="form-control"
                                    value="{{ old('event_date', $formatDate($material->event_date ?? null)) }}"
                                >
                            </div>

                            <div class="col-12">
                                <label class="form-label">Title</label>
                                <input
                                    type="text"
                                    name="title"
                                    id="materialTitleInput"
                                    class="form-control"
                                    value="{{ old('title', $material->title) }}"
                                    placeholder="Contoh: HTML Structure Practice - Trial Class"
                                    required
                                >
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
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea
                                    name="description"
                                    rows="5"
                                    class="form-control"
                                    placeholder="Deskripsi singkat materi, tujuan sesi, dan instruksi umum..."
                                >{{ old('description', $material->description) }}</textarea>
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
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Schedule & Access</h5>
                            <p class="content-card-subtitle mb-0">
                                Atur waktu event dan batas akses public link.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Start Time</label>
                                <input
                                    type="datetime-local"
                                    name="starts_at"
                                    class="form-control"
                                    value="{{ old('starts_at', $formatDateTimeLocal($material->starts_at ?? null)) }}"
                                >
                                <div class="form-text">
                                    Contoh: trial mulai jam 09:00.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">End Time</label>
                                <input
                                    type="datetime-local"
                                    name="ends_at"
                                    id="endsAtInput"
                                    class="form-control"
                                    value="{{ old('ends_at', $formatDateTimeLocal($material->ends_at ?? null)) }}"
                                >
                                <div class="form-text">
                                    Contoh: trial selesai jam 12:00.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Access Starts At</label>
                                <input
                                    type="datetime-local"
                                    name="access_starts_at"
                                    class="form-control"
                                    value="{{ old('access_starts_at', $formatDateTimeLocal($material->access_starts_at ?? null)) }}"
                                >
                                <div class="form-text">
                                    Kalau kosong, sistem akan ikut Start Time.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Access Ends At</label>
                                <input
                                    type="datetime-local"
                                    name="access_ends_at"
                                    id="accessEndsAtInput"
                                    class="form-control"
                                    value="{{ old('access_ends_at', $formatDateTimeLocal($material->access_ends_at ?? null)) }}"
                                >
                                <div class="form-text">
                                    Kalau kosong, sistem akan otomatis set End Time + 1 jam.
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mb-0 mt-3">
                            <i class="bi bi-info-circle me-2"></i>
                            Contoh: trial jam 09:00 - 12:00, akses bisa dibuka sampai jam 13:00.
                            Setelah itu public link akan diblock.
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Cover Image</h5>
                            <p class="content-card-subtitle mb-0">
                                Optional. Cover image akan tampil di halaman public material.
                            </p>
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
                            </div>
                        </div>
                    </div>
                </div>

                @if($isEdit)
                    <div class="content-card mt-4">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Content Blocks</h5>
                                <p class="content-card-subtitle mb-0">
                                    Setelah master material tersimpan, nanti bagian ini dipakai untuk input text, code snippet,
                                    image block, note, dan task.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="alert alert-warning mb-0">
                                <i class="bi bi-cone-striped me-2"></i>
                                Step berikutnya kita tambahkan block manager di sini.
                            </div>
                        </div>
                    </div>
                @endif

            </div>

            <div class="col-xl-4">
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Summary</h5>
                            <p class="content-card-subtitle mb-0">
                                Ringkasan material.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Status</span>
                            <span class="assignment-status-badge {{ $statusClass }}">
                                {{ ucfirst($status) }}
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Type</span>
                            <span class="fw-semibold">
                                {{ ucfirst(old('type', $material->type ?? 'trial')) }}
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Blocks</span>
                            <span class="fw-semibold">
                                {{ $isEdit ? $material->blocks->count() : 0 }}
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-0">
                            <span class="text-muted">Gallery Images</span>
                            <span class="fw-semibold">
                                {{ $isEdit ? $material->images->count() : 0 }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="content-card mb-4">
                    <div class="content-card-header">
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

                <div class="content-card">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Save</h5>
                            <p class="content-card-subtitle mb-0">
                                Simpan perubahan material.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-modern">
                                <i class="bi bi-save me-1"></i>
                                {{ $isEdit ? 'Update Material' : 'Create Material' }}
                            </button>

                            <a href="{{ route('public-learning-materials.index') }}" class="btn btn-outline-secondary btn-modern">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const titleInput = document.getElementById('materialTitleInput');
    const slugInput = document.getElementById('materialSlugInput');
    const endsAtInput = document.getElementById('endsAtInput');
    const accessEndsAtInput = document.getElementById('accessEndsAtInput');

    function slugify(value) {
        return String(value || '')
            .toLowerCase()
            .trim()
            .replace(/['"]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
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

                const toastEl = document.getElementById('appToast');
                const toastBody = toastEl?.querySelector('.toast-body');

                if (window.bootstrap && toastEl && toastBody) {
                    toastBody.textContent = 'Public link berhasil dicopy.';
                    toastEl.classList.remove('bg-danger');
                    toastEl.classList.add('bg-success', 'text-white');

                    bootstrap.Toast.getOrCreateInstance(toastEl, {
                        delay: 2200,
                    }).show();
                }
            } catch (error) {
                alert('Gagal copy link. Silakan copy manual.');
            }
        });
    });
});
</script>
@endpush