@extends('layouts.app-dashboard')

@section('title', 'Learning Videos')

@section('content')
@php
    $videoList = collect($videos ?? []);
    $totalVideos = $videoList->count();
    $totalSize = $videoList->sum('size');
    $fileTypes = $videoList->pluck('extension')->filter()->unique()->values();

    $formatBytes = function ($bytes) {
        $bytes = (float) $bytes;

        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    };
@endphp

<div class="container-fluid px-4 py-4 learning-video-page">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Media Library</div>
                <h1 class="page-title mb-2">Learning Videos</h1>
                <p class="page-subtitle mb-0">
                    Upload video materi dengan mudah melalui drag & drop, lalu sistem akan menyimpannya ke library video pembelajaran.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light btn-modern" id="selectVideoBtn">
                    <i class="bi bi-cloud-arrow-up me-2"></i>Upload Video
                </button>
            </div>
        </div>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <div class="row g-3 mb-4">
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-camera-video-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Videos</div>
                        <div class="stat-value" id="statTotalVideos">{{ $totalVideos }}</div>
                    </div>
                </div>
                <div class="stat-description">Video materi yang tersimpan di library.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-hdd-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Storage Used</div>
                        <div class="stat-value stat-value-small" id="statTotalSize" data-total-size="{{ $totalSize }}">
                            {{ $formatBytes($totalSize) }}
                        </div>
                    </div>
                </div>
                <div class="stat-description">Total ukuran video yang sudah diupload.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-file-earmark-play-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Formats</div>
                        <div class="stat-value stat-value-small" id="statFileTypes">
                            {{ $fileTypes->count() ? $fileTypes->map(fn ($type) => strtoupper($type))->join(', ') : '-' }}
                        </div>
                    </div>
                </div>
                <div class="stat-description">Format video yang tersedia di library.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-folder-lock-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Storage Path</div>
                        <div class="stat-value stat-value-path">Private</div>
                    </div>
                </div>
                <div class="stat-description">storage/app/private/learning-videos/sub-topics</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="content-card upload-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Upload Video</h5>
                        <p class="content-card-subtitle mb-0">
                            Drag file video ke area ini atau pilih file dari komputer.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <form id="learningVideoUploadForm" enctype="multipart/form-data">
                        @csrf

                        <input
                            type="file"
                            id="videoInput"
                            class="d-none"
                            accept=".mp4,.mov,.webm,.mkv,.avi,.m4v,video/*"
                            multiple
                        >

                        <div id="dropZone" class="video-drop-zone">
                            <div class="drop-zone-icon">
                                <i class="bi bi-cloud-arrow-up-fill"></i>
                            </div>

                            <h5 class="drop-zone-title">Drop video di sini</h5>

                            <p class="drop-zone-text mb-3">
                                Support MP4, MOV, WEBM, MKV, AVI, dan M4V. Maksimal <strong>512 MB</strong> per file.
                            </p>

                            <button type="button" class="btn btn-outline-primary btn-modern" id="browseVideoBtn">
                                <i class="bi bi-folder2-open me-2"></i>Pilih File
                            </button>
                        </div>
                    </form>

                    <div id="uploadQueue" class="upload-queue mt-4 d-none">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <h6 class="queue-title mb-0">Upload Progress</h6>
                                <div class="small text-muted">Progress setiap file akan tampil di bawah.</div>
                            </div>

                            <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle" id="queueCount">
                                0 aktif
                            </span>
                        </div>

                        <div id="uploadItems" class="upload-items"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="content-card videos-table-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Video Library</h5>
                        <p class="content-card-subtitle mb-0">
                            Kelola video yang sudah tersimpan untuk materi sub-topic.
                        </p>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <label for="perPageSelect" class="form-label mb-0 small text-muted">Show</label>
                        <select
                            id="perPageSelect"
                            class="form-select form-select-sm"
                            style="width: auto;"
                        >
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="small text-muted">entries</span>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="video-toolbar d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
                        <div class="video-table-info small text-muted" id="videoTableInfo">
                            Showing 0 entries
                        </div>

                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input
                                type="text"
                                id="videoSearchInput"
                                class="form-control form-control-sm"
                                placeholder="Search video..."
                            >
                        </div>
                    </div>

                    <div id="emptyState" class="empty-state {{ $totalVideos ? 'd-none' : '' }}">
                        <div class="empty-icon">
                            <i class="bi bi-camera-video"></i>
                        </div>
                        <h5>Belum ada video</h5>
                        <p class="mb-0">Upload video pertama untuk mulai membuat library materi.</p>
                    </div>

                    <div id="videoTableWrapper" class="video-table-responsive dropdown-safe-table {{ $totalVideos ? '' : 'd-none' }}">
                        <table class="table table-hover align-middle admin-table video-admin-table mb-0">
                            <thead>
                                <tr>
                                    <th class="text-nowrap" style="width: 70px;">No</th>
                                    <th class="text-nowrap col-video">Video</th>
                                    <th class="text-nowrap" style="width: 120px;">Size</th>
                                    <th class="text-nowrap" style="width: 170px;">Uploaded</th>
                                    <th class="text-nowrap col-path">Path</th>
                                    <th class="text-end text-nowrap" style="width: 210px;">Action</th>
                                </tr>
                            </thead>

                            <tbody id="videoTableBody">
                                @foreach($videoList as $video)
                                    <tr
                                        data-video-row
                                        data-filename="{{ $video['filename'] }}"
                                        data-name="{{ strtolower($video['name']) }}"
                                        data-size="{{ $video['size'] }}"
                                        data-extension="{{ $video['extension'] }}"
                                    >
                                        <td class="text-muted" data-row-number>{{ $loop->iteration }}</td>

                                        <td>
                                            <div class="video-cell">
                                                <button
                                                    type="button"
                                                    class="video-thumb-btn"
                                                    data-play-video
                                                    data-filename="{{ $video['filename'] }}"
                                                    data-stream-url="{{ $video['stream_url'] }}"
                                                    data-title="{{ $video['name'] }}"
                                                    aria-label="Preview {{ $video['name'] }}"
                                                >
                                                    <video preload="metadata" muted playsinline>
                                                        <source src="{{ $video['stream_url'] }}" type="video/{{ $video['extension'] === 'm4v' ? 'mp4' : $video['extension'] }}">
                                                    </video>
                                                    <span class="video-play-icon">
                                                        <i class="bi bi-play-fill"></i>
                                                    </span>
                                                </button>

                                                <div class="min-w-0">
                                                    <div class="fw-semibold text-dark video-title" title="{{ $video['filename'] }}">
                                                        {{ $video['name'] }}
                                                    </div>
                                                    <div class="small text-muted text-truncate" title="{{ $video['filename'] }}">
                                                        {{ $video['filename'] }}
                                                    </div>
                                                    <div class="mt-2">
                                                        <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                                                            {{ strtoupper($video['extension']) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="text-nowrap">
                                            <span class="fw-semibold text-dark">{{ $video['size_label'] }}</span>
                                        </td>

                                        <td class="text-nowrap">
                                            <div class="fw-semibold text-dark">{{ $video['last_modified'] }}</div>
                                            <div class="small text-muted">Private upload</div>
                                        </td>

                                        <td>
                                            <div class="video-path-box">
                                                <code>{{ $video['storage_path'] }}</code>
                                            </div>
                                        </td>

                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button
                                                    class="btn btn-light btn-sm btn-modern dropdown-toggle"
                                                    type="button"
                                                    data-bs-toggle="dropdown"
                                                    aria-expanded="false"
                                                >
                                                    Action
                                                </button>

                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button
                                                            type="button"
                                                            class="dropdown-item"
                                                            data-play-video
                                                            data-filename="{{ $video['filename'] }}"
                                                            data-stream-url="{{ $video['stream_url'] }}"
                                                            data-title="{{ $video['name'] }}"
                                                        >
                                                            <i class="bi bi-play-circle me-2"></i>Preview
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button
                                                            type="button"
                                                            class="dropdown-item"
                                                            data-copy-text="{{ $video['filename'] }}"
                                                        >
                                                            <i class="bi bi-copy me-2"></i>Copy Filename
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button
                                                            type="button"
                                                            class="dropdown-item"
                                                            data-copy-text="{{ $video['storage_path'] }}"
                                                        >
                                                            <i class="bi bi-link-45deg me-2"></i>Copy Path
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button
                                                            type="button"
                                                            class="dropdown-item text-danger"
                                                            data-delete-video
                                                            data-filename="{{ $video['filename'] }}"
                                                            data-title="{{ $video['name'] }}"
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

                    <div id="noSearchResult" class="empty-state d-none">
                        <div class="empty-icon">
                            <i class="bi bi-search"></i>
                        </div>
                        <h5>Video tidak ditemukan</h5>
                        <p class="mb-0">Coba gunakan kata kunci lain.</p>
                    </div>

                    <div id="videoPagination" class="video-pagination d-flex justify-content-between align-items-center gap-3 flex-wrap mt-3 d-none">
                        <div class="small text-muted" id="paginationInfo">Showing 0 entries</div>

                        <nav aria-label="Video pagination">
                            <ul class="pagination pagination-sm mb-0" id="paginationList"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Preview Modal --}}
<div class="modal fade" id="videoPreviewModal" tabindex="-1" aria-labelledby="videoPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content custom-modal video-preview-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title" id="videoPreviewModalLabel">Preview Video</h5>
                    <p class="text-muted mb-0" id="videoPreviewFilename">-</p>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="preview-video-wrapper">
                    <video
                        id="previewVideoPlayer"
                        class="preview-video"
                        controls
                        preload="metadata"
                    ></video>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteVideoModal" tabindex="-1" aria-labelledby="deleteVideoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal delete-confirm-modal">
            <div class="modal-header border-0 pb-0">
                <div class="delete-confirm-heading">
                    <div class="delete-confirm-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h5 class="modal-title" id="deleteVideoModalLabel">Delete Video</h5>
                        <p class="text-muted mb-0">Konfirmasi sebelum menghapus video.</p>
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="delete-confirm-message">
                    <div class="delete-confirm-label">Video yang akan dihapus</div>
                    <div class="delete-confirm-name" id="deleteVideoTitle">-</div>
                </div>

                <div class="delete-confirm-warning mt-3">
                    Video yang sudah dihapus tidak bisa dikembalikan.
                </div>

                <input type="hidden" id="deleteVideoFilename">
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button" class="btn btn-danger btn-modern" id="confirmDeleteVideoBtn">
                    <span class="default-text">
                        <i class="bi bi-trash me-2"></i>Delete
                    </span>
                    <span class="loading-text d-none">Deleting...</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let videoPreviewModal;
let deleteVideoModal;
let deleteVideoFilenameValue = null;
let activeUploads = 0;
let currentPage = 1;
let filteredRows = [];

const videoRoutes = {
    store: @json(route('academic.learning-videos.store')),
    destroyTemplate: @json(route('academic.learning-videos.destroy', ['filename' => '__FILENAME__'])),
};

const allowedVideoExtensions = ['mp4', 'mov', 'webm', 'mkv', 'avi', 'm4v'];

document.addEventListener('DOMContentLoaded', function () {
    videoPreviewModal = new bootstrap.Modal(document.getElementById('videoPreviewModal'));
    deleteVideoModal = new bootstrap.Modal(document.getElementById('deleteVideoModal'));

    document.getElementById('selectVideoBtn')?.addEventListener('click', openFilePicker);
    document.getElementById('browseVideoBtn')?.addEventListener('click', openFilePicker);
    document.getElementById('videoInput')?.addEventListener('change', handleInputChange);
    document.getElementById('confirmDeleteVideoBtn')?.addEventListener('click', deleteVideo);
    document.getElementById('videoSearchInput')?.addEventListener('input', function () {
        currentPage = 1;
        refreshVideoTable();
    });
    document.getElementById('perPageSelect')?.addEventListener('change', function () {
        currentPage = 1;
        refreshVideoTable();
    });

    bindDropZone();
    refreshVideoTable();
    refreshVideoStats();

    document.getElementById('videoPreviewModal')?.addEventListener('hidden.bs.modal', function () {
        const player = document.getElementById('previewVideoPlayer');
        player.pause();
        player.removeAttribute('src');
        player.load();
    });
});

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
}

function openFilePicker() {
    document.getElementById('videoInput')?.click();
}

function handleInputChange(event) {
    const files = Array.from(event.target.files || []);
    handleFiles(files);
    event.target.value = '';
}

function bindDropZone() {
    const dropZone = document.getElementById('dropZone');

    if (!dropZone) {
        return;
    }

    dropZone.addEventListener('click', function (event) {
        if (event.target.closest('button')) {
            return;
        }

        openFilePicker();
    });

    ['dragenter', 'dragover'].forEach(function (eventName) {
        dropZone.addEventListener(eventName, function (event) {
            event.preventDefault();
            event.stopPropagation();
            dropZone.classList.add('is-dragging');
        });
    });

    ['dragleave', 'drop'].forEach(function (eventName) {
        dropZone.addEventListener(eventName, function (event) {
            event.preventDefault();
            event.stopPropagation();
            dropZone.classList.remove('is-dragging');
        });
    });

    dropZone.addEventListener('drop', function (event) {
        const files = Array.from(event.dataTransfer.files || []);
        handleFiles(files);
    });
}

function handleFiles(files) {
    if (!files.length) {
        return;
    }

    const validFiles = files.filter(isValidVideoFile);
    const skippedFiles = files.length - validFiles.length;

    if (skippedFiles > 0) {
        showToast(`${skippedFiles} file dilewati karena format tidak didukung.`, 'error');
    }

    if (!validFiles.length) {
        showToast('Gunakan format MP4, MOV, WEBM, MKV, AVI, atau M4V.', 'error');
        return;
    }

    validFiles.forEach(uploadVideo);
}

function isValidVideoFile(file) {
    return allowedVideoExtensions.includes(getExtension(file.name));
}

function uploadVideo(file) {
    activeUploads++;
    updateUploadQueueState();

    const item = createUploadItem(file.name);
    document.getElementById('uploadItems').prepend(item);

    const formData = new FormData();
    formData.append('_token', csrfToken());
    formData.append('video', file);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', videoRoutes.store, true);
    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.upload.addEventListener('progress', function (event) {
        if (!event.lengthComputable) {
            return;
        }

        const percent = Math.round((event.loaded / event.total) * 100);
        updateUploadItem(item, percent, 'Uploading...');
    });

    xhr.addEventListener('load', function () {
        activeUploads = Math.max(activeUploads - 1, 0);
        updateUploadQueueState();

        const response = parseJson(xhr.responseText);

        if (xhr.status >= 200 && xhr.status < 300 && response?.success) {
            updateUploadItem(item, 100, 'Upload selesai');
            item.classList.add('is-complete');

            prependVideoRow(response.data);
            refreshVideoTable();
            refreshVideoStats();
            showToast(response.message || 'Video berhasil diupload.');

            setTimeout(function () {
                item.remove();
                updateUploadQueueState();
            }, 1600);

            return;
        }

        item.classList.add('is-error');
        updateUploadItem(item, 100, 'Upload gagal');
        showToast(getErrorMessage(response) || 'Video gagal diupload. Cek ukuran dan format file.', 'error');
    });

    xhr.addEventListener('error', function () {
        activeUploads = Math.max(activeUploads - 1, 0);
        updateUploadQueueState();

        item.classList.add('is-error');
        updateUploadItem(item, 100, 'Upload gagal');
        showToast('Koneksi bermasalah saat upload video.', 'error');
    });

    xhr.send(formData);
}

function createUploadItem(filename) {
    const item = document.createElement('div');
    item.className = 'upload-item';

    item.innerHTML = `
        <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
            <div class="upload-file-name">${escapeHtml(filename)}</div>
            <div class="upload-status" data-upload-status>Preparing...</div>
        </div>
        <div class="progress">
            <div
                class="progress-bar"
                role="progressbar"
                style="width: 0%;"
                aria-valuenow="0"
                aria-valuemin="0"
                aria-valuemax="100"
                data-upload-progress
            ></div>
        </div>
    `;

    return item;
}

function updateUploadItem(item, percent, status) {
    const progress = item.querySelector('[data-upload-progress]');
    const statusEl = item.querySelector('[data-upload-status]');

    if (progress) {
        progress.style.width = `${percent}%`;
        progress.setAttribute('aria-valuenow', String(percent));
    }

    if (statusEl) {
        statusEl.textContent = status;
    }
}

function updateUploadQueueState() {
    const uploadQueue = document.getElementById('uploadQueue');
    const uploadItems = document.getElementById('uploadItems');
    const totalItems = uploadItems?.querySelectorAll('.upload-item').length || 0;

    uploadQueue?.classList.toggle('d-none', totalItems === 0 && activeUploads === 0);

    const queueCount = document.getElementById('queueCount');

    if (queueCount) {
        queueCount.textContent = `${activeUploads} aktif`;
    }
}

function prependVideoRow(video) {
    const tbody = document.getElementById('videoTableBody');

    if (!tbody || !video) {
        return;
    }

    const row = document.createElement('tr');
    row.setAttribute('data-video-row', '');
    row.setAttribute('data-filename', video.filename || '');
    row.setAttribute('data-name', String(video.name || '').toLowerCase());
    row.setAttribute('data-size', String(video.size || 0));
    row.setAttribute('data-extension', video.extension || '');

    row.innerHTML = buildVideoRowHtml(video);
    tbody.prepend(row);
}

function buildVideoRowHtml(video) {
    const sourceType = video.extension === 'm4v' ? 'mp4' : video.extension;

    return `
        <td class="text-muted" data-row-number>-</td>

        <td>
            <div class="video-cell">
                <button
                    type="button"
                    class="video-thumb-btn"
                    data-play-video
                    data-filename="${escapeAttr(video.filename)}"
                    data-stream-url="${escapeAttr(video.stream_url)}"
                    data-title="${escapeAttr(video.name)}"
                    aria-label="Preview ${escapeAttr(video.name)}"
                >
                    <video preload="metadata" muted playsinline>
                        <source src="${escapeAttr(video.stream_url)}" type="video/${escapeAttr(sourceType)}">
                    </video>
                    <span class="video-play-icon">
                        <i class="bi bi-play-fill"></i>
                    </span>
                </button>

                <div class="min-w-0">
                    <div class="fw-semibold text-dark video-title" title="${escapeAttr(video.filename)}">
                        ${escapeHtml(video.name)}
                    </div>
                    <div class="small text-muted text-truncate" title="${escapeAttr(video.filename)}">
                        ${escapeHtml(video.filename)}
                    </div>
                    <div class="mt-2">
                        <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                            ${escapeHtml(String(video.extension || '').toUpperCase())}
                        </span>
                    </div>
                </div>
            </div>
        </td>

        <td class="text-nowrap">
            <span class="fw-semibold text-dark">${escapeHtml(video.size_label)}</span>
        </td>

        <td class="text-nowrap">
            <div class="fw-semibold text-dark">${escapeHtml(video.last_modified)}</div>
            <div class="small text-muted">Private upload</div>
        </td>

        <td>
            <div class="video-path-box">
                <code>${escapeHtml(video.storage_path)}</code>
            </div>
        </td>

        <td class="text-end">
            <div class="dropdown">
                <button
                    class="btn btn-light btn-sm btn-modern dropdown-toggle"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >
                    Action
                </button>

                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <button
                            type="button"
                            class="dropdown-item"
                            data-play-video
                            data-filename="${escapeAttr(video.filename)}"
                            data-stream-url="${escapeAttr(video.stream_url)}"
                            data-title="${escapeAttr(video.name)}"
                        >
                            <i class="bi bi-play-circle me-2"></i>Preview
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item" data-copy-text="${escapeAttr(video.filename)}">
                            <i class="bi bi-copy me-2"></i>Copy Filename
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item" data-copy-text="${escapeAttr(video.storage_path)}">
                            <i class="bi bi-link-45deg me-2"></i>Copy Path
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button
                            type="button"
                            class="dropdown-item text-danger"
                            data-delete-video
                            data-filename="${escapeAttr(video.filename)}"
                            data-title="${escapeAttr(video.name)}"
                        >
                            <i class="bi bi-trash me-2"></i>Delete
                        </button>
                    </li>
                </ul>
            </div>
        </td>
    `;
}

document.addEventListener('click', function (event) {
    const playButton = event.target.closest('[data-play-video]');
    const copyButton = event.target.closest('[data-copy-text]');
    const deleteButton = event.target.closest('[data-delete-video]');
    const pageButton = event.target.closest('[data-page]');

    if (playButton) {
        openPreview(playButton);
        return;
    }

    if (copyButton) {
        copyText(copyButton.getAttribute('data-copy-text'));
        return;
    }

    if (deleteButton) {
        openDeleteModal(deleteButton);
        return;
    }

    if (pageButton) {
        const page = pageButton.getAttribute('data-page');
        changePage(page);
    }
});

function openPreview(button) {
    const filename = button.getAttribute('data-filename') || '-';
    const title = button.getAttribute('data-title') || 'Preview Video';
    const streamUrl = button.getAttribute('data-stream-url');

    document.getElementById('videoPreviewModalLabel').textContent = title;
    document.getElementById('videoPreviewFilename').textContent = filename;

    const player = document.getElementById('previewVideoPlayer');
    player.src = streamUrl;
    player.load();

    videoPreviewModal.show();
}

function openDeleteModal(button) {
    deleteVideoFilenameValue = button.getAttribute('data-filename');

    document.getElementById('deleteVideoFilename').value = deleteVideoFilenameValue || '';
    document.getElementById('deleteVideoTitle').textContent = button.getAttribute('data-title') || deleteVideoFilenameValue || '-';

    deleteVideoModal.show();
}

async function deleteVideo() {
    const filename = document.getElementById('deleteVideoFilename').value;

    if (!filename) {
        return;
    }

    const button = document.getElementById('confirmDeleteVideoBtn');
    setButtonLoading(button, true);

    try {
        const response = await fetch(buildDestroyUrl(filename), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok || !result?.success) {
            throw new Error(result?.message || 'Video gagal dihapus.');
        }

        const row = findVideoRow(filename);

        if (row) {
            row.remove();
        }

        deleteVideoModal.hide();
        refreshVideoTable();
        refreshVideoStats();
        showToast(result.message || 'Video berhasil dihapus.');
    } catch (error) {
        showToast(error.message || 'Terjadi kesalahan saat menghapus video.', 'error');
    } finally {
        setButtonLoading(button, false);
    }
}

function refreshVideoTable() {
    const rows = Array.from(document.querySelectorAll('[data-video-row]'));
    const keyword = String(document.getElementById('videoSearchInput')?.value || '').trim().toLowerCase();
    const perPage = getPerPage();

    filteredRows = rows.filter(function (row) {
        const filename = String(row.getAttribute('data-filename') || '').toLowerCase();
        const name = String(row.getAttribute('data-name') || '').toLowerCase();

        return !keyword || filename.includes(keyword) || name.includes(keyword);
    });

    const totalRows = rows.length;
    const totalFiltered = filteredRows.length;
    const totalPages = Math.max(Math.ceil(totalFiltered / perPage), 1);

    if (currentPage > totalPages) {
        currentPage = totalPages;
    }

    rows.forEach(row => row.classList.add('d-none'));

    const start = (currentPage - 1) * perPage;
    const end = start + perPage;

    filteredRows.slice(start, end).forEach(function (row, index) {
        row.classList.remove('d-none');

        const numberCell = row.querySelector('[data-row-number]');

        if (numberCell) {
            numberCell.textContent = start + index + 1;
        }
    });

    updateTableVisibility(totalRows, totalFiltered, keyword);
    renderPagination(totalFiltered, perPage);
}

function updateTableVisibility(totalRows, totalFiltered, keyword) {
    const tableWrapper = document.getElementById('videoTableWrapper');
    const emptyState = document.getElementById('emptyState');
    const noSearchResult = document.getElementById('noSearchResult');
    const pagination = document.getElementById('videoPagination');

    if (totalRows === 0) {
        tableWrapper?.classList.add('d-none');
        emptyState?.classList.remove('d-none');
        noSearchResult?.classList.add('d-none');
        pagination?.classList.add('d-none');
        updatePaginationText(0, 0, 0);
        return;
    }

    emptyState?.classList.add('d-none');

    if (totalFiltered === 0 && keyword) {
        tableWrapper?.classList.add('d-none');
        noSearchResult?.classList.remove('d-none');
        pagination?.classList.add('d-none');
        updatePaginationText(0, 0, 0);
        return;
    }

    tableWrapper?.classList.remove('d-none');
    noSearchResult?.classList.add('d-none');
    pagination?.classList.toggle('d-none', totalFiltered <= getPerPage());
}

function renderPagination(totalItems, perPage) {
    const paginationList = document.getElementById('paginationList');

    if (!paginationList) {
        return;
    }

    paginationList.innerHTML = '';

    if (totalItems === 0) {
        updatePaginationText(0, 0, 0);
        return;
    }

    const totalPages = Math.max(Math.ceil(totalItems / perPage), 1);
    const startItem = (currentPage - 1) * perPage + 1;
    const endItem = Math.min(currentPage * perPage, totalItems);

    updatePaginationText(startItem, endItem, totalItems);

    paginationList.insertAdjacentHTML('beforeend', paginationItem('Previous', currentPage - 1, currentPage === 1));

    getPaginationPages(totalPages).forEach(function (page) {
        if (page === '...') {
            paginationList.insertAdjacentHTML('beforeend', `<li class="page-item disabled"><span class="page-link">...</span></li>`);
            return;
        }

        paginationList.insertAdjacentHTML('beforeend', paginationItem(page, page, false, page === currentPage));
    });

    paginationList.insertAdjacentHTML('beforeend', paginationItem('Next', currentPage + 1, currentPage === totalPages));
}

function paginationItem(label, page, disabled = false, active = false) {
    return `
        <li class="page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}">
            <button class="page-link" type="button" data-page="${page}" ${disabled ? 'disabled' : ''}>
                ${label}
            </button>
        </li>
    `;
}

function getPaginationPages(totalPages) {
    if (totalPages <= 7) {
        return Array.from({ length: totalPages }, (_, index) => index + 1);
    }

    const pages = [1];
    const start = Math.max(currentPage - 1, 2);
    const end = Math.min(currentPage + 1, totalPages - 1);

    if (start > 2) {
        pages.push('...');
    }

    for (let page = start; page <= end; page++) {
        pages.push(page);
    }

    if (end < totalPages - 1) {
        pages.push('...');
    }

    pages.push(totalPages);

    return pages;
}

function changePage(page) {
    const perPage = getPerPage();
    const totalPages = Math.max(Math.ceil(filteredRows.length / perPage), 1);
    const targetPage = Number(page);

    if (!targetPage || targetPage < 1 || targetPage > totalPages) {
        return;
    }

    currentPage = targetPage;
    refreshVideoTable();
}

function updatePaginationText(start, end, total) {
    const text = total > 0
        ? `Showing ${start} to ${end} of ${total} entries`
        : 'Showing 0 entries';

    const tableInfo = document.getElementById('videoTableInfo');
    const paginationInfo = document.getElementById('paginationInfo');

    if (tableInfo) {
        tableInfo.textContent = text;
    }

    if (paginationInfo) {
        paginationInfo.textContent = text;
    }
}

function refreshVideoStats() {
    const rows = Array.from(document.querySelectorAll('[data-video-row]'));
    const totalSize = rows.reduce((sum, row) => sum + Number(row.getAttribute('data-size') || 0), 0);
    const extensions = [...new Set(rows.map(row => String(row.getAttribute('data-extension') || '').toUpperCase()).filter(Boolean))];

    document.getElementById('statTotalVideos').textContent = rows.length;
    document.getElementById('statTotalSize').textContent = formatBytes(totalSize);
    document.getElementById('statFileTypes').textContent = extensions.length ? extensions.join(', ') : '-';
}

function getPerPage() {
    return Number(document.getElementById('perPageSelect')?.value || 10);
}

function buildDestroyUrl(filename) {
    return videoRoutes.destroyTemplate.replace('__FILENAME__', encodeURIComponent(filename));
}

function findVideoRow(filename) {
    return Array.from(document.querySelectorAll('[data-video-row]'))
        .find(row => row.getAttribute('data-filename') === filename) || null;
}

function setButtonLoading(button, loading = true) {
    const defaultText = button.querySelector('.default-text');
    const loadingText = button.querySelector('.loading-text');

    button.disabled = loading;

    if (defaultText) defaultText.classList.toggle('d-none', loading);
    if (loadingText) loadingText.classList.toggle('d-none', !loading);
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');

    if (!container) {
        return;
    }

    const toastId = 'toast-' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';

    container.insertAdjacentHTML('beforeend', `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${escapeHtml(message)}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);

    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl, { delay: 2500 });

    toast.show();

    toastEl.addEventListener('hidden.bs.toast', function () {
        toastEl.remove();
    });
}

function copyText(text) {
    if (!text) {
        return;
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text)
            .then(() => showToast('Informasi video berhasil disalin.'))
            .catch(() => fallbackCopy(text));
        return;
    }

    fallbackCopy(text);
}

function fallbackCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';

    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();

    try {
        document.execCommand('copy');
        showToast('Informasi video berhasil disalin.');
    } catch (error) {
        showToast('Browser tidak mengizinkan copy otomatis.', 'error');
    }

    textarea.remove();
}

function parseJson(value) {
    try {
        return JSON.parse(value);
    } catch (error) {
        return null;
    }
}

function getErrorMessage(response) {
    if (!response) {
        return null;
    }

    if (response.message) {
        return response.message;
    }

    if (response.errors) {
        const firstKey = Object.keys(response.errors)[0];
        return firstKey ? response.errors[firstKey]?.[0] : null;
    }

    return null;
}

function formatBytes(bytes) {
    bytes = Number(bytes || 0);

    if (bytes <= 0) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    const power = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);

    return `${Math.round((bytes / Math.pow(1024, power)) * 100) / 100} ${units[power]}`;
}

function getExtension(filename) {
    return String(filename || '').split('.').pop().toLowerCase();
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function escapeAttr(value) {
    return escapeHtml(value);
}
</script>
@endpush
