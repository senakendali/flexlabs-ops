@extends('layouts.app-dashboard')

@section('title', 'Record Attendance')

@section('content')
@php
    $getStudentLabel = function ($student) {
        return $student->full_name
            ?? $student->student_name
            ?? $student->name
            ?? $student->email
            ?? ('Student #' . $student->id);
    };

    $getBatchLabel = function ($batch) {
        $programName = $batch->program->name ?? null;
        $batchName = $batch->name
            ?? $batch->batch_name
            ?? $batch->code
            ?? ('Batch #' . $batch->id);

        return $programName ? $programName . ' - ' . $batchName : $batchName;
    };

    $getScheduleValue = function ($schedule, ?string $column) {
        return $column ? ($schedule->{$column} ?? null) : null;
    };

    $getScheduleTitle = function ($schedule) {
        return $schedule->title
            ?? $schedule->topic
            ?? $schedule->session_title
            ?? $schedule->name
            ?? ('Live Session #' . $schedule->id);
    };

    $dateValue = $getScheduleValue($instructorSchedule, $dateColumn);
    $startValue = $getScheduleValue($instructorSchedule, $startTimeColumn);
    $endValue = $getScheduleValue($instructorSchedule, $endTimeColumn);

    $scheduleDate = $dateValue
        ? \Illuminate\Support\Carbon::parse($dateValue)->format('d M Y')
        : '-';

    $startLabel = $startValue
        ? \Illuminate\Support\Carbon::parse($startValue)->format('H:i')
        : null;

    $endLabel = $endValue
        ? \Illuminate\Support\Carbon::parse($endValue)->format('H:i')
        : null;

    $timeRange = trim(($startLabel ?: '') . ' - ' . ($endLabel ?: ''), ' -');

    $presentLikeCount = $attendances
        ->filter(fn ($attendance) => in_array($attendance->status, \App\Models\StudentAttendance::COUNTED_AS_PRESENT, true))
        ->count();

    $onlineCount = $attendances
        ->where('attendance_mode', \App\Models\StudentAttendance::MODE_ONLINE)
        ->count();

    $offlineCount = $attendances
        ->where('attendance_mode', \App\Models\StudentAttendance::MODE_OFFLINE)
        ->count();
@endphp

<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Management</div>
                <h1 class="page-title mb-2">Record Attendance</h1>
                <p class="page-subtitle mb-0">
                    Catat kehadiran student untuk live session, termasuk status hadir dan mode online/offline.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('academic.attendances.index') }}" class="btn btn-light btn-modern">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>
            </div>
        </div>
    </div>

    <div id="attendanceAlert" class="alert d-none" role="alert"></div>

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
        <div class="alert alert-danger">
            <div class="fw-bold mb-2">Mohon cek kembali input berikut:</div>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div>
                        <div class="stat-title">Session Date</div>
                        <div class="stat-value fs-5">{{ $scheduleDate }}</div>
                    </div>
                </div>
                <div class="stat-description">{{ $timeRange ?: 'Time not set' }}</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Students</div>
                        <div class="stat-value">{{ $students->count() }}</div>
                    </div>
                </div>
                <div class="stat-description">Student aktif pada batch ini.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Present Count</div>
                        <div class="stat-value">{{ $presentLikeCount }}</div>
                    </div>
                </div>
                <div class="stat-description">Present dan late dihitung hadir.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-laptop"></i>
                    </div>
                    <div>
                        <div class="stat-title">Mode</div>
                        <div class="stat-value fs-6">{{ $onlineCount }} Online</div>
                    </div>
                </div>
                <div class="stat-description">{{ $offlineCount }} offline attendance recorded.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Session Info</h5>
                <p class="content-card-subtitle mb-0">
                    {{ $getScheduleTitle($instructorSchedule) }}
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="text-muted small">Batch</div>
                    <div class="fw-semibold text-dark">{{ $getBatchLabel($batch) }}</div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">Date</div>
                    <div class="fw-semibold text-dark">{{ $scheduleDate }}</div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">Time</div>
                    <div class="fw-semibold text-dark">{{ $timeRange ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <form
        method="POST"
        action="{{ route('academic.attendances.store', $instructorSchedule) }}"
        id="attendanceForm"
    >
        @csrf

        <div class="content-card">
            <div class="content-card-header">
                <div>
                    <h5 class="content-card-title mb-1">Student Attendance List</h5>
                    <p class="content-card-subtitle mb-0">
                        Status <strong>Present</strong> dan <strong>Late</strong> dihitung hadir untuk assessment.
                    </p>
                </div>

                @if($students->count())
                    <button type="submit" class="btn btn-primary btn-modern attendance-submit-btn">
                        <i class="bi bi-save me-2"></i>Save Attendance
                    </button>
                @endif
            </div>

            <div class="content-card-body">
                @if($students->count())
                    <div class="alert alert-info">
                        Gunakan <strong>Mode</strong> untuk membedakan student hadir secara online atau offline.
                        Mode hanya aktif jika statusnya Present atau Late.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th width="60">#</th>
                                    <th>Student</th>
                                    <th class="text-nowrap">Status</th>
                                    <th class="text-nowrap">Mode</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($students as $student)
                                    @php
                                        $attendance = $attendances->get($student->id);

                                        $selectedStatus = old(
                                            'attendances.' . $loop->index . '.status',
                                            $attendance->status ?? \App\Models\StudentAttendance::STATUS_PRESENT
                                        );

                                        $selectedMode = old(
                                            'attendances.' . $loop->index . '.attendance_mode',
                                            $attendance->attendance_mode ?? \App\Models\StudentAttendance::MODE_OFFLINE
                                        );

                                        $modeDisabled = !in_array($selectedStatus, \App\Models\StudentAttendance::COUNTED_AS_PRESENT, true);
                                    @endphp

                                    <tr>
                                        <td class="text-muted">
                                            {{ $loop->iteration }}
                                        </td>

                                        <td>
                                            <input
                                                type="hidden"
                                                name="attendances[{{ $loop->index }}][student_id]"
                                                value="{{ $student->id }}"
                                            >

                                            <div class="fw-bold text-dark">
                                                {{ $getStudentLabel($student) }}
                                            </div>

                                            <div class="text-muted small">
                                                {{ $student->email ?? $student->phone ?? 'Student ID: ' . $student->id }}
                                            </div>
                                        </td>

                                        <td class="text-nowrap">
                                            <select
                                                name="attendances[{{ $loop->index }}][status]"
                                                class="form-select form-select-sm attendance-status-select"
                                                data-row-index="{{ $loop->index }}"
                                            >
                                                @foreach(\App\Models\StudentAttendance::STATUSES as $statusValue => $statusLabel)
                                                    <option value="{{ $statusValue }}" {{ $selectedStatus === $statusValue ? 'selected' : '' }}>
                                                        {{ $statusLabel }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td class="text-nowrap">
                                            <select
                                                name="attendances[{{ $loop->index }}][attendance_mode]"
                                                class="form-select form-select-sm attendance-mode-select"
                                                data-row-index="{{ $loop->index }}"
                                                {{ $modeDisabled ? 'disabled' : '' }}
                                            >
                                                @foreach(\App\Models\StudentAttendance::ATTENDANCE_MODES as $modeValue => $modeLabel)
                                                    <option value="{{ $modeValue }}" {{ $selectedMode === $modeValue ? 'selected' : '' }}>
                                                        {{ $modeLabel }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td>
                                            <input
                                                type="text"
                                                name="attendances[{{ $loop->index }}][notes]"
                                                class="form-control form-control-sm"
                                                value="{{ old('attendances.' . $loop->index . '.notes', $attendance->notes ?? '') }}"
                                                placeholder="Optional notes..."
                                            >
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end gap-2 flex-wrap mt-4">
                        <a href="{{ route('academic.attendances.index') }}" class="btn btn-outline-secondary btn-modern">
                            Cancel
                        </a>

                        <button type="submit" class="btn btn-primary btn-modern attendance-submit-btn">
                            <i class="bi bi-save me-2"></i>Save Attendance
                        </button>
                    </div>
                @else
                    <div class="empty-state-box">
                        <div class="empty-state-icon">
                            <i class="bi bi-person-x"></i>
                        </div>

                        <h5 class="empty-state-title">Student belum tersedia</h5>
                        <p class="empty-state-text mb-0">
                            Tidak ada student aktif pada batch ini. Pastikan data enrollment student sudah tersedia.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const countedAsPresent = ['present', 'late'];

    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('appToast');

        if (!toastEl) {
            showInlineAlert(message, type);
            return;
        }

        const toastBody = toastEl.querySelector('.toast-body');

        if (toastBody) {
            toastBody.innerHTML = message;
        }

        toastEl.classList.remove('bg-success', 'bg-danger', 'text-white');

        if (type === 'success') {
            toastEl.classList.add('bg-success', 'text-white');
        } else {
            toastEl.classList.add('bg-danger', 'text-white');
        }

        const toast = bootstrap.Toast.getOrCreateInstance(toastEl, {
            delay: 3000
        });

        toast.show();
    }

    function showInlineAlert(message, type = 'success') {
        const alertBox = document.getElementById('attendanceAlert');

        if (!alertBox) {
            alert(message.replace(/<br\s*\/?>/gi, '\n'));
            return;
        }

        alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');

        if (type === 'success') {
            alertBox.classList.add('alert-success');
        } else {
            alertBox.classList.add('alert-danger');
        }

        alertBox.innerHTML = message;
    }

    function syncAttendanceMode(rowIndex, status) {
        const modeSelect = document.querySelector(
            `.attendance-mode-select[data-row-index="${rowIndex}"]`
        );

        if (!modeSelect) {
            return;
        }

        const shouldEnable = countedAsPresent.includes(status);

        modeSelect.disabled = !shouldEnable;

        if (!shouldEnable) {
            modeSelect.value = '';
        } else if (!modeSelect.value) {
            modeSelect.value = 'offline';
        }
    }

    function setSubmitLoading(isLoading) {
        document.querySelectorAll('.attendance-submit-btn').forEach(function (button) {
            button.disabled = isLoading;

            if (isLoading) {
                if (!button.dataset.originalText) {
                    button.dataset.originalText = button.innerHTML;
                }

                button.innerHTML = `
                    <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                    Saving...
                `;
            } else {
                button.innerHTML = button.dataset.originalText || '<i class="bi bi-save me-2"></i>Save Attendance';
            }
        });
    }

    function renderValidationErrors(errors) {
        let messages = [];

        if (errors && typeof errors === 'object') {
            Object.keys(errors).forEach(function (key) {
                const value = errors[key];

                if (Array.isArray(value)) {
                    value.forEach(function (message) {
                        messages.push(message);
                    });
                } else if (value) {
                    messages.push(value);
                }
            });
        }

        return messages.length
            ? messages.join('<br>')
            : 'Mohon cek kembali data attendance.';
    }

    document.querySelectorAll('.attendance-status-select').forEach(function (statusSelect) {
        statusSelect.addEventListener('change', function () {
            syncAttendanceMode(
                statusSelect.dataset.rowIndex,
                statusSelect.value
            );
        });

        syncAttendanceMode(
            statusSelect.dataset.rowIndex,
            statusSelect.value
        );
    });

    const attendanceForm = document.getElementById('attendanceForm');

    if (attendanceForm) {
        attendanceForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            setSubmitLoading(true);

            const formData = new FormData(attendanceForm);

            try {
                const response = await fetch(attendanceForm.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    if (response.status === 422) {
                        showToast(renderValidationErrors(data.errors), 'error');
                    } else {
                        showToast(data.message || 'Gagal menyimpan attendance.', 'error');
                    }

                    setSubmitLoading(false);
                    return;
                }

                showToast(data.message || 'Attendance berhasil disimpan.', 'success');

                setTimeout(function () {
                    window.location.reload();
                }, 900);
            } catch (error) {
                showToast('Gagal menghubungi server.', 'error');
                setSubmitLoading(false);
            }
        });
    }
});
</script>
@endpush