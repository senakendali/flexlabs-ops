@extends('layouts.app-dashboard')

@section('title', 'HR Attendance')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <div class="text-uppercase fw-bold text-primary small mb-2" style="letter-spacing:.12em;">
                        People Operations
                    </div>

                    <h1 class="h2 fw-bold text-dark mb-2">
                        Staff Attendance
                    </h1>

                    <p class="text-muted mb-0">
                        Dummy attendance workspace untuk menyiapkan alur check-in, check-out, keterlambatan, izin, cuti, dan rekap kehadiran.
                    </p>
                </div>

                <span class="badge rounded-pill text-bg-warning-subtle text-warning-emphasis px-3 py-2">
                    <i class="bi bi-cone-striped me-1"></i>
                    Dummy Data
                </span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['label' => 'Present', 'value' => $summary['present'] ?? 0, 'icon' => 'bi bi-person-check-fill'],
            ['label' => 'Late', 'value' => $summary['late'] ?? 0, 'icon' => 'bi bi-clock-history'],
            ['label' => 'Leave / Permit', 'value' => $summary['leave'] ?? 0, 'icon' => 'bi bi-calendar2-x-fill'],
            ['label' => 'Absent', 'value' => $summary['absent'] ?? 0, 'icon' => 'bi bi-person-x-fill'],
        ] as $item)
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small fw-semibold mb-2">{{ $item['label'] }}</div>
                                <div class="display-6 fw-bold text-dark">{{ number_format($item['value']) }}</div>
                            </div>

                            <span class="d-inline-flex align-items-center justify-content-center rounded-4 text-primary"
                                  style="width:46px;height:46px;background:rgba(91,62,142,.10);">
                                <i class="{{ $item['icon'] }}"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 px-4 pt-4 pb-3">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div>
                    <h2 class="h5 fw-bold text-dark mb-1">Daily Attendance</h2>
                    <p class="small text-muted mb-0">Tanggal aktif: {{ $selectedDate }}</p>
                </div>

                <button type="button" class="btn btn-outline-secondary" disabled>
                    <i class="bi bi-download me-2"></i>Export
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">Employee</th>
                        <th class="py-3">Department</th>
                        <th class="py-3">Date</th>
                        <th class="py-3">Check In</th>
                        <th class="py-3">Check Out</th>
                        <th class="py-3">Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($attendanceRows as $row)
                        <tr>
                            <td class="px-4 py-3 fw-semibold text-dark">{{ $row['employee'] }}</td>
                            <td class="py-3">{{ $row['department'] }}</td>
                            <td class="py-3">{{ $row['date'] }}</td>
                            <td class="py-3">{{ $row['check_in'] }}</td>
                            <td class="py-3">{{ $row['check_out'] }}</td>
                            <td class="py-3">
                                <span class="badge rounded-pill text-bg-secondary">
                                    {{ $row['status'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                Belum ada data attendance.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
