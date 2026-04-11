@extends('layouts.app-dashboard')

@section('title', 'Dashboard')

@section('content')
<div class="dashboard-content">
    <div class="container-fluid py-4">
        <div class="mb-4">
            <h3 class="fw-bold mb-1">Dashboard</h3>
            <p class="text-muted mb-0">
                Monitor seluruh aktivitas operasional FlexLabs secara real-time dalam satu dashboard.
            </p>
        </div>

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-2">
                <div class="card dashboard-stat-card h-100">
                    <div class="card-body d-flex flex-column">
                        
                        {{-- TOP SECTION --}}
                        <div class="d-flex align-items-start gap-3">
                            <div class="stat-icon">
                                <i class="bi bi-journal-code"></i>
                            </div>

                            <div>
                                <div class="text-muted small">Total Program</div>
                                <h4 class="fw-bold mb-0">{{ $stats['programs']['total'] }}</h4>
                            </div>
                        </div>

                        {{-- BOTTOM SECTION --}}
                        <div class="mt-auto pt-2">
                            <small class="text-success">
                                <i class="bi bi-check-circle"></i> {{ $stats['programs']['active'] }} aktif
                            </small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-2">
                <div class="card dashboard-stat-card h-100">
                    <div class="card-body d-flex flex-column">

                        <div class="d-flex align-items-start gap-3">
                            <div class="stat-icon">
                                <i class="bi bi-person-workspace"></i>
                            </div>

                            <div>
                                <div class="text-muted small">Total Instructor</div>
                                <h4 class="fw-bold mb-0">{{ $stats['instructors']['total'] }}</h4>
                            </div>
                        </div>

                        <div class="mt-auto pt-2">
                            <small class="text-success">
                                <i class="bi bi-check-circle"></i> {{ $stats['instructors']['active'] }} aktif
                            </small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-2">
                <div class="card dashboard-stat-card h-100">
                    <div class="card-body d-flex flex-column">

                        <div class="d-flex align-items-start gap-3">
                            <div class="stat-icon">
                                <i class="bi bi-tools"></i>
                            </div>

                            <div>
                                <div class="text-muted small">Total Equipment</div>
                                <h4 class="fw-bold mb-0">{{ $stats['equipments']['total'] }}</h4>
                            </div>
                        </div>

                        <div class="mt-auto pt-2">
                            <small class="text-warning-emphasis">
                                <i class="bi bi-arrow-repeat"></i> {{ $equipmentOverview['borrowed'] }} sedang dipinjam
                            </small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-2">
                <div class="card dashboard-stat-card h-100">
                    <div class="card-body d-flex flex-column">

                        <div class="d-flex align-items-start gap-3">
                            <div class="stat-icon">
                                <i class="bi bi-palette"></i>
                            </div>

                            <div>
                                <div class="text-muted small">Trial Themes</div>
                                <h4 class="fw-bold mb-0">{{ $stats['trialThemes']['total'] }}</h4>
                            </div>
                        </div>

                        <div class="mt-auto pt-2">
                            <small class="text-info-emphasis">
                                <i class="bi bi-stars"></i> {{ $stats['trialThemes']['active'] }} aktif
                            </small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-2">
                <div class="card dashboard-stat-card h-100">
                    <div class="card-body d-flex flex-column">

                        <div class="d-flex align-items-start gap-3">
                            <div class="stat-icon">
                                <i class="bi bi-calendar-event"></i>
                            </div>

                            <div>
                                <div class="text-muted small">Trial Schedule</div>
                                <h4 class="fw-bold mb-0">{{ $stats['trialSchedules']['total'] }}</h4>
                            </div>
                        </div>

                        <div class="mt-auto pt-2">
                            <small class="text-secondary-emphasis">
                                <i class="bi bi-clock-history"></i> {{ $upcomingSchedules->count() }} jadwal terdekat
                            </small>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-2">
                <div class="card dashboard-stat-card h-100">
                    <div class="card-body d-flex flex-column">

                        <div class="d-flex align-items-start gap-3">
                            <div class="stat-icon">
                                <i class="bi bi-people"></i>
                            </div>

                            <div>
                                <div class="text-muted small">Trial Participant</div>
                                <h4 class="fw-bold mb-0">{{ $stats['trialParticipants']['total'] }}</h4>
                            </div>
                        </div>

                        <div class="mt-auto pt-2">
                            <small class="text-danger-emphasis">
                                <i class="bi bi-person-plus"></i> {{ $stats['trialParticipants']['new_this_month'] }} bulan ini
                            </small>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- Trial Overview --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-1">Ringkasan Trial Class</h5>
                            <p class="text-muted small mb-0">Pantau perkembangan pendaftaran dan status peserta</p>
                        </div>
                        <a href="{{ Route::has('trial-participants.index') ? route('trial-participants.index') : 'javascript:void(0)' }}" class="btn btn-sm btn-outline-primary">
                            Lihat Peserta
                        </a>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="trial-mini-card">
                                    <div class="small text-muted mb-1">Registered</div>
                                    <div class="fs-4 fw-bold">{{ $participantStatusCounts['registered'] ?? 0 }}</div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="trial-mini-card">
                                    <div class="small text-muted mb-1">Contacted</div>
                                    <div class="fs-4 fw-bold text-info">{{ $participantStatusCounts['contacted'] ?? 0 }}</div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="trial-mini-card">
                                    <div class="small text-muted mb-1">Confirmed</div>
                                    <div class="fs-4 fw-bold text-primary">{{ $participantStatusCounts['confirmed'] ?? 0 }}</div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="trial-mini-card">
                                    <div class="small text-muted mb-1">Attended</div>
                                    <div class="fs-4 fw-bold text-success">{{ $participantStatusCounts['attended'] ?? 0 }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="small fw-semibold">Progress follow up peserta</span>
                                <span class="small text-muted">{{ $followUpProgress }}%</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div
                                    class="progress-bar bg-primary"
                                    role="progressbar"
                                    style="width: {{ $followUpProgress }}%;"
                                    aria-valuenow="{{ $followUpProgress }}"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                ></div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Program</th>
                                            <th>Theme</th>
                                            <th>Peserta</th>
                                            <th>Conversion</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($programThemeSummary as $item)
                                            <tr>
                                                <td>{{ $item['program_name'] }}</td>
                                                <td>{{ $item['theme_name'] }}</td>
                                                <td>{{ $item['participant_count'] }}</td>
                                                <td>
                                                    <span class="badge {{ $item['conversion_badge_class'] }}">
                                                        {{ $item['conversion_label'] }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">
                                                    Belum ada data peserta trial.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Access --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="fw-bold mb-1">Quick Access</h5>
                        <p class="text-muted small mb-0">Shortcut ke modul utama</p>
                    </div>

                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ Route::has('programs.index') ? route('programs.index') : 'javascript:void(0)' }}" class="quick-link-card">
                                <span><i class="bi bi-journal-code me-2"></i> Manage Program</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>

                            <a href="{{ Route::has('instructors.index') ? route('instructors.index') : 'javascript:void(0)' }}" class="quick-link-card">
                                <span><i class="bi bi-person-workspace me-2"></i> Manage Instructor</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>

                            <a href="{{ Route::has('equipments.index') ? route('equipments.index') : 'javascript:void(0)' }}" class="quick-link-card">
                                <span><i class="bi bi-tools me-2"></i> Manage Equipment</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>

                            <a href="{{ Route::has('trial-themes.index') ? route('trial-themes.index') : 'javascript:void(0)' }}" class="quick-link-card">
                                <span><i class="bi bi-palette me-2"></i> Trial Themes</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>

                            <a href="{{ Route::has('trial-schedules.index') ? route('trial-schedules.index') : 'javascript:void(0)' }}" class="quick-link-card">
                                <span><i class="bi bi-calendar-event me-2"></i> Trial Schedule</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>

                            <a href="{{ Route::has('trial-participants.index') ? route('trial-participants.index') : 'javascript:void(0)' }}" class="quick-link-card">
                                <span><i class="bi bi-people me-2"></i> Trial Participant</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Schedules and Equipment --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-1">Upcoming Trial Schedule</h5>
                            <p class="text-muted small mb-0">Jadwal trial terdekat yang perlu dipantau</p>
                        </div>
                        <a href="{{ Route::has('trial-schedules.index') ? route('trial-schedules.index') : 'javascript:void(0)' }}" class="btn btn-sm btn-outline-primary">
                            Lihat Jadwal
                        </a>
                    </div>

                    <div class="card-body">
                        @forelse ($upcomingSchedules as $schedule)
                            <div class="schedule-item">
                                <div>
                                    <div class="fw-semibold">{{ $schedule->name }}</div>
                                    <div class="small text-muted">
                                        {{ \Illuminate\Support\Carbon::parse($schedule->schedule_date)->translatedFormat('l, d F Y') }}
                                        •
                                        {{ \Illuminate\Support\Carbon::parse($schedule->start_time)->format('H:i') }}
                                        -
                                        {{ \Illuminate\Support\Carbon::parse($schedule->end_time)->format('H:i') }}
                                    </div>
                                </div>

                                <span class="badge bg-primary-subtle text-primary">
                                    {{ $scheduleParticipantCounts[$schedule->id] ?? 0 }} peserta
                                </span>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">
                                Belum ada jadwal trial terdekat.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-1">Equipment Overview</h5>
                            <p class="text-muted small mb-0">Status alat yang tersedia dan dipinjam</p>
                        </div>
                        <a href="{{ Route::has('equipments.index') ? route('equipments.index') : 'javascript:void(0)' }}" class="btn btn-sm btn-outline-primary">
                            Lihat Equipment
                        </a>
                    </div>

                    <div class="card-body">
                        <div class="equipment-status-item">
                            <div class="d-flex align-items-center gap-2">
                                <span class="status-dot bg-success"></span>
                                <span class="fw-semibold">Available</span>
                            </div>
                            <span>{{ $equipmentOverview['available'] }}</span>
                        </div>

                        <div class="equipment-status-item">
                            <div class="d-flex align-items-center gap-2">
                                <span class="status-dot bg-warning"></span>
                                <span class="fw-semibold">Borrowed</span>
                            </div>
                            <span>{{ $equipmentOverview['borrowed'] }}</span>
                        </div>

                        <div class="equipment-status-item">
                            <div class="d-flex align-items-center gap-2">
                                <span class="status-dot bg-danger"></span>
                                <span class="fw-semibold">Maintenance</span>
                            </div>
                            <span>{{ $equipmentOverview['maintenance'] }}</span>
                        </div>

                        <div class="equipment-status-item">
                            <div class="d-flex align-items-center gap-2">
                                <span class="status-dot bg-secondary"></span>
                                <span class="fw-semibold">Inactive</span>
                            </div>
                            <span>{{ $equipmentOverview['inactive'] }}</span>
                        </div>

                        <hr>

                        <div class="small text-muted mb-2">Ringkasan cepat</div>
                        <ul class="list-unstyled mb-0 small">
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Total equipment: {{ $stats['equipments']['total'] }}
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-arrow-repeat text-info me-2"></i>
                                Sedang dipinjam: {{ $equipmentOverview['borrowed'] }}
                            </li>
                            <li>
                                <i class="bi bi-exclamation-circle text-warning me-2"></i>
                                Perlu maintenance: {{ $equipmentOverview['maintenance'] }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Activity --}}
        <div class="row g-3">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="fw-bold mb-1">Aktivitas Terbaru</h5>
                        <p class="text-muted small mb-0">Ringkasan aktivitas terbaru dari data yang ada</p>
                    </div>

                    <div class="card-body">
                        @forelse ($recentActivities as $activity)
                            <div class="activity-item">
                                <div class="activity-icon {{ $activity['icon_class'] }}">
                                    <i class="bi {{ $activity['icon'] }}"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $activity['title'] }}</div>
                                    <div class="small text-muted">{{ $activity['subtitle'] }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">
                                Belum ada aktivitas terbaru.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection