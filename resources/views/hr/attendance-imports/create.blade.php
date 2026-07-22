@extends('layouts.app-dashboard')

@section('title', 'Upload Attendance')

@section('content')
@php
    $selectedWorkingDays = old(
        'default_working_days',
        $defaultSettings['default_working_days'] ?? [1, 2, 3, 4, 5]
    );
@endphp

<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Human Resources</div>
                <h1 class="page-title mb-2">Upload Attendance</h1>
                <p class="page-subtitle mb-0">
                    Upload an Evertime Excel file and prepare attendance data for HR review.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('hr.attendance-imports.index') }}" class="btn btn-light btn-modern">
                    <i class="bi bi-arrow-left me-2"></i>Back to Imports
                </a>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <div class="fw-semibold mb-2">Upload could not be processed.</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            {{ session('error') }}
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('hr.attendance-imports.store') }}"
        enctype="multipart/form-data"
        id="attendanceUploadForm"
    >
        @csrf

        <div class="row g-4">
            <div class="col-12 col-xl-7">
                <div class="content-card h-100">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Attendance File</h5>
                            <p class="content-card-subtitle mb-0">
                                Supported formats: .xlsx and .xls, maximum file size 15 MB.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <label
                            for="attendanceFile"
                            id="attendanceDropzone"
                            class="border border-2 border-dashed rounded-4 p-5 text-center d-block bg-light-subtle"
                            style="cursor: pointer;"
                        >
                            <div
                                class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary mb-3"
                                style="width: 64px; height: 64px;"
                            >
                                <i class="bi bi-cloud-arrow-up fs-2"></i>
                            </div>

                            <h5 class="fw-bold mb-2">Choose Evertime Excel File</h5>
                            <p class="text-muted mb-0">
                                Click here or drag the file into this area.
                            </p>

                            <input
                                type="file"
                                name="file"
                                id="attendanceFile"
                                class="d-none"
                                accept=".xlsx,.xls"
                                required
                            >
                        </label>

                        <div id="filePreview" class="content-card mt-3 d-none">
                            <div class="content-card-body">
                                <div class="d-flex align-items-center gap-3">
                                    <div
                                        class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success"
                                        style="width: 48px; height: 48px;"
                                    >
                                        <i class="bi bi-file-earmark-excel fs-5"></i>
                                    </div>

                                    <div class="flex-grow-1 overflow-hidden">
                                        <div id="fileName" class="fw-semibold text-dark text-truncate">-</div>
                                        <div id="fileSize" class="small text-muted">-</div>
                                    </div>

                                    <button type="button" class="btn btn-sm btn-outline-danger" id="removeFile">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info border-0 mt-4 mb-0">
                            <div class="d-flex gap-3 align-items-start">
                                <i class="bi bi-info-circle-fill mt-1"></i>
                                <div>
                                    <div class="fw-semibold mb-1">What happens after upload?</div>
                                    <div class="small">
                                        The system reads employee data, working-hours templates, clock-in/out records,
                                        and creates missing workdays for HR review before final attendance is saved.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="content-card">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Import Settings</h5>
                            <p class="content-card-subtitle mb-0">
                                Used as fallback when the Excel file does not contain complete schedule information.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="mb-3">
                            <label for="sheet_name" class="form-label">Sheet Name</label>
                            <input
                                type="text"
                                id="sheet_name"
                                name="sheet_name"
                                class="form-control"
                                value="{{ old('sheet_name', $defaultSettings['sheet_name'] ?? 'Attendance') }}"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">Default Working Days</label>

                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($workingDayOptions as $value => $label)
                                    <label class="border rounded-3 px-3 py-2 bg-white">
                                        <input
                                            type="checkbox"
                                            name="default_working_days[]"
                                            value="{{ $value }}"
                                            class="form-check-input me-2"
                                            {{ in_array($value, $selectedWorkingDays, true) ? 'checked' : '' }}
                                        >
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="default_start_time" class="form-label">Default Start</label>
                                <input
                                    type="time"
                                    id="default_start_time"
                                    name="default_start_time"
                                    class="form-control"
                                    value="{{ old('default_start_time', $defaultSettings['default_start_time'] ?? '08:00') }}"
                                >
                            </div>

                            <div class="col-6">
                                <label for="default_end_time" class="form-label">Default End</label>
                                <input
                                    type="time"
                                    id="default_end_time"
                                    name="default_end_time"
                                    class="form-control"
                                    value="{{ old('default_end_time', $defaultSettings['default_end_time'] ?? '17:00') }}"
                                >
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="late_tolerance_minutes" class="form-label">Late Tolerance</label>
                            <div class="input-group">
                                <input
                                    type="number"
                                    min="0"
                                    max="240"
                                    id="late_tolerance_minutes"
                                    name="late_tolerance_minutes"
                                    class="form-control"
                                    value="{{ old('late_tolerance_minutes', $defaultSettings['late_tolerance_minutes'] ?? 0) }}"
                                >
                                <span class="input-group-text">minutes</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="duplicate_action" class="form-label">Duplicate Handling</label>
                            <select name="duplicate_action" id="duplicate_action" class="form-select">
                                <option value="update" {{ old('duplicate_action', 'update') === 'update' ? 'selected' : '' }}>
                                    Update existing attendance
                                </option>
                                <option value="skip" {{ old('duplicate_action') === 'skip' ? 'selected' : '' }}>
                                    Skip existing attendance
                                </option>
                                <option value="error" {{ old('duplicate_action') === 'error' ? 'selected' : '' }}>
                                    Stop when duplicate exists
                                </option>
                            </select>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="generate_missing_rows" value="0">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="generate_missing_rows"
                                value="1"
                                id="generate_missing_rows"
                                {{ old('generate_missing_rows', true) ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="generate_missing_rows">
                                Detect missing workdays
                            </label>
                            <div class="form-text">
                                Missing workdays will be created as Needs Review.
                            </div>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input type="hidden" name="include_future_dates" value="0">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="include_future_dates"
                                value="1"
                                id="include_future_dates"
                                {{ old('include_future_dates', false) ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="include_future_dates">
                                Include future dates
                            </label>
                            <div class="form-text">
                                Keep this disabled to avoid marking future schedules as missing.
                            </div>
                        </div>

                        @if ($workingHourTemplates->count())
                            <div class="mb-4">
                                <div class="small text-muted mb-2">Existing Working Templates</div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($workingHourTemplates as $template)
                                        <span class="badge rounded-pill bg-light text-dark border">
                                            {{ $template->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="content-card-footer d-flex justify-content-end align-items-center gap-2 flex-wrap px-4 py-3 border-top">
                        <a
                            href="{{ route('hr.attendance-imports.index') }}"
                            class="btn btn-outline-secondary btn-modern"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary btn-modern"
                            id="uploadSubmitButton"
                        >
                            <span class="default-text">
                                <i class="bi bi-upload me-2"></i>Upload & Process
                            </span>

                            <span class="loading-text d-none">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Processing...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const uploadForm = document.getElementById('attendanceUploadForm');
    const fileInput = document.getElementById('attendanceFile');
    const dropzone = document.getElementById('attendanceDropzone');
    const filePreview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const removeFile = document.getElementById('removeFile');
    const uploadSubmitButton = document.getElementById('uploadSubmitButton');

    function formatBytes(bytes) {
        if (!bytes) return '0 KB';

        const units = ['B', 'KB', 'MB', 'GB'];
        const index = Math.min(
            Math.floor(Math.log(bytes) / Math.log(1024)),
            units.length - 1
        );

        return `${(bytes / Math.pow(1024, index)).toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
    }

    function syncFilePreview() {
        const file = fileInput.files?.[0];

        if (!file) {
            filePreview.classList.add('d-none');
            fileName.textContent = '-';
            fileSize.textContent = '-';
            return;
        }

        fileName.textContent = file.name;
        fileSize.textContent = formatBytes(file.size);
        filePreview.classList.remove('d-none');
    }

    fileInput.addEventListener('change', syncFilePreview);

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, event => {
            event.preventDefault();
            dropzone.classList.add('border-primary');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, event => {
            event.preventDefault();
            dropzone.classList.remove('border-primary');
        });
    });

    dropzone.addEventListener('drop', event => {
        const files = event.dataTransfer.files;

        if (!files?.length) return;

        const transfer = new DataTransfer();
        transfer.items.add(files[0]);
        fileInput.files = transfer.files;
        syncFilePreview();
    });

    removeFile.addEventListener('click', () => {
        fileInput.value = '';
        syncFilePreview();
    });

    uploadForm.addEventListener('submit', () => {
        uploadSubmitButton.disabled = true;
        uploadSubmitButton.querySelector('.default-text').classList.add('d-none');
        uploadSubmitButton.querySelector('.loading-text').classList.remove('d-none');
    });
</script>
@endpush
