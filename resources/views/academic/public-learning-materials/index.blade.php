@extends('layouts.app-dashboard')

@section('title', 'Trial & Workshop Materials')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Management</div>
                <h1 class="page-title mb-2">Trial & Workshop Materials</h1>
                <p class="page-subtitle mb-0">
                    Kelola materi public untuk <strong>trial class</strong> dan <strong>workshop</strong>,
                    lengkap dengan text, code snippet, image, dan expired link.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('public-learning-materials.create') }}" class="btn btn-primary btn-modern">
                    <i class="bi bi-plus-circle me-2"></i>Add Material
                </a>
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

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-collection"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total</div>
                        <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Semua material.</div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-stars"></i>
                    </div>
                    <div>
                        <div class="stat-title">Trial</div>
                        <div class="stat-value">{{ $stats['trial'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Materi trial class.</div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-easel2"></i>
                    </div>
                    <div>
                        <div class="stat-title">Workshop</div>
                        <div class="stat-value">{{ $stats['workshop'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Materi workshop.</div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-send-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Published</div>
                        <div class="stat-value">{{ $stats['published'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Link aktif.</div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div>
                        <div class="stat-title">Draft</div>
                        <div class="stat-value">{{ $stats['draft'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Belum publish.</div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-archive"></i>
                    </div>
                    <div>
                        <div class="stat-title">Archived</div>
                        <div class="stat-value">{{ $stats['archived'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Diarsipkan.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Materials</h5>
                <p class="content-card-subtitle mb-0">
                    Cari berdasarkan judul, subtitle, deskripsi, atau nama instructor.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('public-learning-materials.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="">All Types</option>
                            <option value="trial" {{ request('type') === 'trial' ? 'selected' : '' }}>Trial</option>
                            <option value="workshop" {{ request('type') === 'workshop' ? 'selected' : '' }}>Workshop</option>
                        </select>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                            <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                    </div>

                    <div class="col-xl-4 col-md-12">
                        <label class="form-label">Keyword</label>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Cari judul, instructor, atau deskripsi..."
                        >
                    </div>

                    <div class="col-xl-2 col-md-12">
                        <div class="d-flex gap-2 justify-content-xl-end flex-wrap">
                            <a href="{{ route('public-learning-materials.index') }}" class="btn btn-outline-secondary btn-modern">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                            </a>

                            <button type="submit" class="btn btn-primary btn-modern">
                                <i class="bi bi-funnel me-2"></i>Apply
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Material List</h5>
                <p class="content-card-subtitle mb-0">
                    Klik action untuk edit, publish, archive, duplicate, copy link, atau delete material.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if(($materials ?? collect())->count())
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th class="text-nowrap">Type</th>
                                <th class="text-nowrap">Schedule</th>
                                <th class="text-nowrap">Access</th>
                                <th class="text-nowrap">Content</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-end text-nowrap">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($materials as $material)
                                @php
                                    $statusClass = match($material->status) {
                                        'published' => 'status-published',
                                        'archived' => 'status-closed',
                                        default => 'status-draft',
                                    };

                                    $statusLabel = ucfirst($material->status);

                                    $typeClass = $material->type === 'workshop'
                                        ? 'deadline-badge deadline-open'
                                        : 'assignment-status-badge status-open';

                                    $publicUrl = $material->public_url ?? route('public-learning-materials.show', [
                                        'token' => $material->public_token,
                                        'slug' => $material->slug,
                                    ]);

                                    $isExpired = $material->access_ends_at && now()->gt($material->access_ends_at);
                                    $isNotStarted = $material->access_starts_at && now()->lt($material->access_starts_at);

                                    $accessClass = $isExpired
                                        ? 'deadline-badge deadline-closed'
                                        : ($isNotStarted ? 'assignment-status-badge status-draft' : 'deadline-badge deadline-open');

                                    $accessLabel = $isExpired
                                        ? 'Expired'
                                        : ($isNotStarted ? 'Not Started' : 'Open');
                                @endphp

                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="table-avatar">
                                                <i class="bi bi-file-earmark-code"></i>
                                            </div>

                                            <div class="min-w-0">
                                                <div class="fw-semibold text-dark">{{ $material->title }}</div>

                                                <div class="text-muted small">
                                                    {{ $material->subtitle ?: 'No subtitle' }}
                                                </div>

                                                <div class="text-muted small text-truncate" style="max-width: 420px;">
                                                    {{ $publicUrl }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="{{ $typeClass }}">
                                            {{ ucfirst($material->type) }}
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <div class="fw-semibold">
                                            {{ optional($material->event_date)->format('d M Y') ?? '-' }}
                                        </div>

                                        <div class="text-muted small">
                                            @if($material->starts_at && $material->ends_at)
                                                {{ $material->starts_at->format('H:i') }} - {{ $material->ends_at->format('H:i') }}
                                            @else
                                                No schedule
                                            @endif
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="{{ $accessClass }}">
                                            {{ $accessLabel }}
                                        </span>

                                        <div class="text-muted small mt-1">
                                            Until {{ optional($material->access_ends_at)->format('d M Y H:i') ?? '-' }}
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <div class="fw-semibold">
                                            {{ $material->blocks_count ?? 0 }} Blocks
                                        </div>

                                        <div class="text-muted small">
                                            {{ $material->images_count ?? 0 }} Images
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="assignment-status-badge {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
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
                                                    <a
                                                        href="{{ route('public-learning-materials.edit', $material) }}"
                                                        class="dropdown-item"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit
                                                    </a>
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item copy-link-btn"
                                                        data-copy-value="{{ $publicUrl }}"
                                                    >
                                                        <i class="bi bi-copy me-2"></i>Copy Public Link
                                                    </button>
                                                </li>

                                                @if($material->status !== 'published')
                                                    <li>
                                                        <form
                                                            method="POST"
                                                            action="{{ route('public-learning-materials.publish', $material) }}"
                                                            class="m-0"
                                                        >
                                                            @csrf

                                                            <button type="submit" class="dropdown-item text-success">
                                                                <i class="bi bi-send-check me-2"></i>Publish
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif

                                                @if($material->status !== 'archived')
                                                    <li>
                                                        <form
                                                            method="POST"
                                                            action="{{ route('public-learning-materials.archive', $material) }}"
                                                            class="m-0"
                                                        >
                                                            @csrf

                                                            <button type="submit" class="dropdown-item">
                                                                <i class="bi bi-archive me-2"></i>Archive
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif

                                                <li>
                                                    <form
                                                        method="POST"
                                                        action="{{ route('public-learning-materials.duplicate', $material) }}"
                                                        class="m-0"
                                                    >
                                                        @csrf

                                                        <button type="submit" class="dropdown-item">
                                                            <i class="bi bi-files me-2"></i>Duplicate
                                                        </button>
                                                    </form>
                                                </li>

                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>

                                                <li>
                                                    <form
                                                        method="POST"
                                                        action="{{ route('public-learning-materials.destroy', $material) }}"
                                                        class="m-0"
                                                        onsubmit="return confirm('Yakin mau hapus material ini? Block dan image juga akan ikut terhapus.')"
                                                    >
                                                        @csrf
                                                        @method('DELETE')

                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="bi bi-trash me-2"></i>Delete
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $materials->links() }}
                </div>
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-file-earmark-code"></i>
                    </div>

                    <h5 class="empty-state-title">Belum ada material</h5>
                    <p class="empty-state-text mb-0">
                        Mulai buat materi public untuk trial class atau workshop. Nanti student bisa akses lewat link khusus.
                    </p>

                    <div class="mt-3">
                        <a href="{{ route('public-learning-materials.create') }}" class="btn btn-primary btn-modern">
                            <i class="bi bi-plus-circle me-2"></i>Create First Material
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.copy-link-btn').forEach(function (button) {
        button.addEventListener('click', async function () {
            const value = button.dataset.copyValue || '';

            if (!value) {
                return;
            }

            try {
                await navigator.clipboard.writeText(value);

                if (window.bootstrap) {
                    const toastEl = document.getElementById('appToast');
                    const toastBody = toastEl?.querySelector('.toast-body');

                    if (toastEl && toastBody) {
                        toastBody.textContent = 'Public link berhasil dicopy.';
                        toastEl.classList.remove('bg-danger');
                        toastEl.classList.add('bg-success', 'text-white');

                        bootstrap.Toast.getOrCreateInstance(toastEl, {
                            delay: 2200,
                        }).show();
                    }
                }
            } catch (error) {
                alert('Gagal copy link. Silakan copy manual.');
            }
        });
    });
});
</script>
@endpush