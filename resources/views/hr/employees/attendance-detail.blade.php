@extends('layouts.app-dashboard')

@section('title', 'Detail Kehadiran Employee')

@section('content')
@php
    $employeeData = $employee ?? null;
    $employeeOverview = $overview ?? [];
    $calendarRows = collect($calendar ?? []);
    $exceptionRows = collect($exceptions ?? []);
    $trendData = $monthlyTrend ?? [];

    $selectedMonth = request('month')
        ?: \Carbon\Carbon::parse(
            $period['date_from'] ?? now()->startOfMonth()
        )->format('Y-m');

    try {
        $monthAnchor = \Carbon\Carbon::createFromFormat(
            'Y-m',
            $selectedMonth
        )->startOfMonth();
    } catch (\Throwable) {
        $monthAnchor = now()->startOfMonth();
    }

    $previousMonthUrl = route(
        'hr.dashboard.employee-detail',
        [
            'employee' => $employeeData?->id,
            'month' => $monthAnchor
                ->copy()
                ->subMonthNoOverflow()
                ->format('Y-m'),
        ]
    );

    $nextMonthUrl = route(
        'hr.dashboard.employee-detail',
        [
            'employee' => $employeeData?->id,
            'month' => $monthAnchor
                ->copy()
                ->addMonthNoOverflow()
                ->format('Y-m'),
        ]
    );

    $monthlyReportUrl = route(
        'hr.dashboard.monthly-report',
        [
            'month' => $selectedMonth,
            'work_team' => $employeeData?->work_team,
        ]
    );

    $calendarByDate = $calendarRows->keyBy('date');

    $monthDates = collect(
        \Carbon\CarbonPeriod::create(
            \Carbon\Carbon::parse(
                $period['date_from']
                    ?? $monthAnchor->toDateString()
            ),
            \Carbon\Carbon::parse(
                $period['date_to']
                    ?? $monthAnchor
                        ->copy()
                        ->endOfMonth()
                        ->toDateString()
            )
        )
    )->map(
        fn (\Carbon\Carbon $date) => $date->copy()
    );

    $calendarLeadingDays = max(
        0,
        ($monthDates->first()?->dayOfWeekIso ?? 1) - 1
    );

    $formatNumber = static fn ($value): string =>
        number_format((int) $value);

    $formatPercent = static fn ($value): string =>
        number_format((float) $value, 1) . '%';

    $formatDate = static function (
        $value,
        string $fallback = '-'
    ): string {
        if (! $value) {
            return $fallback;
        }

        try {
            return \Carbon\Carbon::parse($value)
                ->translatedFormat('d M Y');
        } catch (\Throwable) {
            return $fallback;
        }
    };

    $formatTime = static function (
        $value,
        string $fallback = '-'
    ): string {
        if (! filled($value)) {
            return $fallback;
        }

        return substr((string) $value, 0, 5);
    };

    $statusClass = static function (
        ?string $code
    ): string {
        return match ($code) {
            'OT' => 'is-on-time',
            'LT' => 'is-late',
            'EL' => 'is-excused-late',
            'HL' => 'is-half-leave',
            'LV' => 'is-full-leave',
            'AB' => 'is-absent',
            'MS' => 'is-missing',
            'OD' => 'is-off-day',
            'PH' => 'is-holiday',
            'PR' => 'is-present',
            default => 'is-empty',
        };
    };

    $statusIcon = static function (
        ?string $code
    ): string {
        return match ($code) {
            'OT' => 'bi-check-circle-fill',
            'LT' => 'bi-alarm-fill',
            'EL' => 'bi-shield-check',
            'HL', 'LV' => 'bi-calendar2-minus-fill',
            'AB' => 'bi-person-x-fill',
            'MS' => 'bi-question-circle-fill',
            'OD' => 'bi-moon-stars-fill',
            'PH' => 'bi-calendar2-event-fill',
            'PR' => 'bi-person-check-fill',
            default => 'bi-dash-circle',
        };
    };

    $leaveLabels = [
        'sick_leave' => 'Sakit',
        'annual_leave' => 'Cuti Tahunan',
        'unpaid_leave' => 'Cuti Tidak Dibayar',
        'permission' => 'Izin',
        'other' => 'Lainnya',
    ];

    $leaveDurationLabels = [
        'full_day' => 'Hari Penuh',
        'half_day' => 'Setengah Hari',
        'custom_hours' => 'Jam Tertentu',
    ];

    $employeeInitial = mb_strtoupper(
        mb_substr(
            $employeeData?->name ?: '?',
            0,
            1
        )
    );

    $employeeStatusLabel = $employeeData?->is_active
        ? 'Aktif'
        : 'Tidak Aktif';

    $employeeStatusClass = $employeeData?->is_active
        ? 'bg-success-subtle text-success-emphasis'
        : 'bg-secondary-subtle text-secondary-emphasis';

    $defaultTemplate = $employeeData
        ?->defaultWorkingHourTemplate;

    $defaultSchedule = $defaultTemplate
        ? (
            $defaultTemplate->name
            . ' · '
            . $formatTime($defaultTemplate->start_time)
            . '–'
            . $formatTime($defaultTemplate->end_time)
        )
        : (
            filled($employeeData?->default_start_time)
            || filled($employeeData?->default_end_time)
                ? (
                    'Jadwal Employee · '
                    . $formatTime(
                        $employeeData?->default_start_time
                    )
                    . '–'
                    . $formatTime(
                        $employeeData?->default_end_time
                    )
                )
                : 'Belum ada jadwal kerja default'
        );
@endphp

<div class="container-fluid px-4 py-4 employee-attendance-detail-page">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">
                    Employee Attendance
                </div>

                <h1 class="page-title mb-2">
                    Detail Kehadiran Employee
                </h1>

                <p class="page-subtitle mb-0">
                    Lihat ringkasan kehadiran, catatan yang perlu diperhatikan, dan detail attendance setiap tanggal.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a
                    href="{{ $monthlyReportUrl }}"
                    class="btn btn-light btn-modern"
                >
                    <i class="bi bi-calendar3 me-2"></i>
                    Laporan Bulanan
                </a>

                <a
                    href="{{ route('hr.dashboard') }}"
                    class="btn btn-light btn-modern"
                >
                    <i class="bi bi-arrow-left me-2"></i>
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="employee-profile-card mb-4">
        <div class="employee-profile-main">
            <div class="employee-profile-avatar">
                {{ $employeeInitial }}
            </div>

            <div class="employee-profile-content">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h2 class="employee-profile-name mb-0">
                        {{ $employeeData?->name
                            ?? 'Employee belum dikenali' }}
                    </h2>

                    <span class="badge rounded-pill {{ $employeeStatusClass }}">
                        {{ $employeeStatusLabel }}
                    </span>
                </div>

                <div class="employee-profile-meta">
                    <span>
                        <i class="bi bi-person-vcard-fill"></i>
                        {{ $employeeData?->employee_number
                            ?? 'Nomor employee belum tersedia' }}
                    </span>

                    <span>
                        <i class="bi bi-diagram-3-fill"></i>
                        {{ $employeeData?->work_team
                            ?: 'Tim belum ditentukan' }}
                    </span>

                    <span>
                        <i class="bi bi-briefcase-fill"></i>
                        {{ $employeeData?->employee_type
                            ?: 'Tipe employee belum ditentukan' }}
                    </span>
                </div>

                <div class="employee-profile-schedule">
                    <i class="bi bi-clock-fill"></i>

                    <div>
                        <span>Jadwal Kerja Default</span>
                        <strong>{{ $defaultSchedule }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="employee-period-selector">
            <div>
                <span class="employee-period-label">
                    Periode
                </span>

                <strong>
                    {{ $period['label'] ?? '-' }}
                </strong>
            </div>

            <div class="employee-period-navigation">
                <a
                    href="{{ $previousMonthUrl }}"
                    class="btn btn-outline-secondary"
                    aria-label="Bulan sebelumnya"
                >
                    <i class="bi bi-chevron-left"></i>
                </a>

                <form
                    method="GET"
                    action="{{ route(
                        'hr.dashboard.employee-detail',
                        $employeeData?->id
                    ) }}"
                    id="employeeMonthFilterForm"
                >
                    <input
                        type="month"
                        class="form-control"
                        name="month"
                        id="employeeMonth"
                        value="{{ $selectedMonth }}"
                    >
                </form>

                <a
                    href="{{ $nextMonthUrl }}"
                    class="btn btn-outline-secondary"
                    aria-label="Bulan berikutnya"
                >
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xxl-3 col-xl-4 col-md-6">
            <div class="employee-kpi-card">
                <span class="employee-kpi-icon">
                    <i class="bi bi-calendar-check-fill"></i>
                </span>

                <div class="employee-kpi-label">
                    Hari Kerja Terjadwal
                </div>

                <div class="employee-kpi-value">
                    {{ $formatNumber(
                        $employeeOverview[
                            'expected_workdays'
                        ] ?? 0
                    ) }}
                </div>

                <div class="employee-kpi-help">
                    Total hari kerja employee pada periode ini.
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-4 col-md-6">
            <div class="employee-kpi-card">
                <span class="employee-kpi-icon">
                    <i class="bi bi-person-check-fill"></i>
                </span>

                <div class="employee-kpi-label">
                    Tingkat Kehadiran
                </div>

                <div class="employee-kpi-value">
                    {{ $formatPercent(
                        $employeeOverview[
                            'presence_rate'
                        ] ?? 0
                    ) }}
                </div>

                <div class="employee-kpi-help">
                    {{ $formatNumber(
                        $employeeOverview[
                            'present_days'
                        ] ?? 0
                    ) }}
                    hari hadir.
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-4 col-md-6">
            <div class="employee-kpi-card">
                <span class="employee-kpi-icon">
                    <i class="bi bi-alarm-fill"></i>
                </span>

                <div class="employee-kpi-label">
                    Ketepatan Waktu
                </div>

                <div class="employee-kpi-value">
                    {{ $formatPercent(
                        $employeeOverview[
                            'on_time_rate'
                        ] ?? 0
                    ) }}
                </div>

                <div class="employee-kpi-help">
                    {{ $formatNumber(
                        $employeeOverview[
                            'on_time_days'
                        ] ?? 0
                    ) }}
                    hari tepat waktu.
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-4 col-md-6">
            <div class="employee-kpi-card">
                <span class="employee-kpi-icon">
                    <i class="bi bi-clock-history"></i>
                </span>

                <div class="employee-kpi-label">
                    Terlambat
                </div>

                <div class="employee-kpi-value">
                    {{ $formatNumber(
                        $employeeOverview['late_days'] ?? 0
                    ) }}
                </div>

                <div class="employee-kpi-help">
                    Rata-rata
                    {{ number_format(
                        (float) (
                            $employeeOverview[
                                'average_late_minutes'
                            ] ?? 0
                        ),
                        1
                    ) }}
                    menit keterlambatan.
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-4 col-md-6">
            <div class="employee-kpi-card">
                <span class="employee-kpi-icon">
                    <i class="bi bi-calendar2-minus-fill"></i>
                </span>

                <div class="employee-kpi-label">
                    Cuti / Izin
                </div>

                <div class="employee-kpi-value">
                    {{ $formatNumber(
                        $employeeOverview[
                            'approved_leave_days'
                        ] ?? 0
                    ) }}
                </div>

                <div class="employee-kpi-help">
                    {{ $formatNumber(
                        $employeeOverview[
                            'full_day_leave_days'
                        ] ?? 0
                    ) }}
                    hari penuh ·
                    {{ $formatNumber(
                        $employeeOverview[
                            'half_day_leave_days'
                        ] ?? 0
                    ) }}
                    setengah hari.
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-4 col-md-6">
            <div class="employee-kpi-card">
                <span class="employee-kpi-icon">
                    <i class="bi bi-person-x-fill"></i>
                </span>

                <div class="employee-kpi-label">
                    Tidak Hadir
                </div>

                <div class="employee-kpi-value">
                    {{ $formatNumber(
                        $employeeOverview[
                            'absent_days'
                        ] ?? 0
                    ) }}
                </div>

                <div class="employee-kpi-help">
                    Tidak hadir tanpa cuti atau izin yang tercatat.
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-4 col-md-6">
            <div class="employee-kpi-card">
                <span class="employee-kpi-icon">
                    <i class="bi bi-box-arrow-right"></i>
                </span>

                <div class="employee-kpi-label">
                    Clock Out Diisi Otomatis
                </div>

                <div class="employee-kpi-value">
                    {{ $formatNumber(
                        $employeeOverview[
                            'auto_clock_out_days'
                        ] ?? 0
                    ) }}
                </div>

                <div class="employee-kpi-help">
                    Jam pulang tidak tercatat dan diisi berdasarkan jadwal kerja.
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-4 col-md-6">
            <div class="employee-kpi-card">
                <span class="employee-kpi-icon">
                    <i class="bi bi-exclamation-circle-fill"></i>
                </span>

                <div class="employee-kpi-label">
                    Catatan Perlu Perhatian
                </div>

                <div class="employee-kpi-value">
                    {{ $formatNumber(
                        $exceptionRows->count()
                    ) }}
                </div>

                <div class="employee-kpi-help">
                    Terlambat, tidak hadir, pulang lebih awal, atau clock out tidak tercatat.
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">
                            Kalender Kehadiran
                        </h5>

                        <p class="content-card-subtitle mb-0">
                            Menampilkan status kehadiran employee pada setiap tanggal. Klik tanggal untuk melihat jam masuk, jam pulang, jadwal kerja, dan catatan.
                        </p>
                    </div>

                    <span class="badge rounded-pill bg-light text-dark border">
                        {{ $period['label'] ?? '-' }}
                    </span>
                </div>

                <div class="content-card-body">
                    <div class="employee-calendar-weekdays">
                        @foreach ([
                            'Sen',
                            'Sel',
                            'Rab',
                            'Kam',
                            'Jum',
                            'Sab',
                            'Min',
                        ] as $dayLabel)
                            <div>{{ $dayLabel }}</div>
                        @endforeach
                    </div>

                    <div class="employee-calendar-grid">
                        @for (
                            $index = 0;
                            $index < $calendarLeadingDays;
                            $index++
                        )
                            <div class="employee-calendar-empty"></div>
                        @endfor

                        @foreach ($monthDates as $date)
                            @php
                                $dateKey = $date->toDateString();
                                $record = $calendarByDate->get(
                                    $dateKey
                                );

                                $payload = [
                                    'date' => $dateKey,
                                    'day_name' =>
                                        $date->translatedFormat('l'),
                                    'employee_name' =>
                                        $employeeData?->name,
                                    'employee_number' =>
                                        $employeeData
                                            ?->employee_number,
                                    'work_team' =>
                                        $employeeData?->work_team,
                                    'code' =>
                                        $record['code'] ?? '-',
                                    'label' =>
                                        $record['label']
                                            ?? 'Belum Ada Data Final',
                                    'status' =>
                                        $record['status']
                                            ?? 'no_record',
                                    'clock_in' =>
                                        $record['clock_in'] ?? null,
                                    'clock_out' =>
                                        $record['clock_out'] ?? null,
                                    'late_minutes' =>
                                        $record[
                                            'late_minutes'
                                        ] ?? 0,
                                    'leave_type' =>
                                        $record[
                                            'leave_type'
                                        ] ?? null,
                                    'leave_duration' =>
                                        $record[
                                            'leave_duration'
                                        ] ?? null,
                                    'is_excused' =>
                                        $record[
                                            'is_excused'
                                        ] ?? false,
                                    'is_auto_clock_out' =>
                                        $record[
                                            'is_auto_clock_out'
                                        ] ?? false,
                                    'remarks' =>
                                        $record['remarks'] ?? null,
                                    'template' =>
                                        $record['template'] ?? null,
                                    'scheduled_start_time' =>
                                        $record[
                                            'scheduled_start_time'
                                        ] ?? null,
                                    'scheduled_end_time' =>
                                        $record[
                                            'scheduled_end_time'
                                        ] ?? null,
                                    'arrival_status' =>
                                        $record[
                                            'arrival_status'
                                        ] ?? null,
                                    'departure_status' =>
                                        $record[
                                            'departure_status'
                                        ] ?? null,
                                ];

                                $encodedPayload = base64_encode(
                                    json_encode(
                                        $payload,
                                        JSON_UNESCAPED_UNICODE
                                            | JSON_UNESCAPED_SLASHES
                                    )
                                );

                                $isWeekend = in_array(
                                    $date->dayOfWeekIso,
                                    [6, 7],
                                    true
                                );
                            @endphp

                            <button
                                type="button"
                                class="employee-calendar-day {{ $statusClass($record['code'] ?? '-') }} {{ $isWeekend ? 'is-weekend' : '' }}"
                                data-attendance-detail="{{ $encodedPayload }}"
                            >
                                <span class="employee-calendar-date">
                                    {{ $date->format('d') }}
                                </span>

                                <span class="employee-calendar-status">
                                    <i class="bi {{ $statusIcon($record['code'] ?? '-') }}"></i>
                                    {{ $record['code'] ?? '-' }}
                                </span>

                                <span class="employee-calendar-label">
                                    {{ $record['label']
                                        ?? 'Belum Ada Data Final' }}
                                </span>

                                @if (
                                    $record[
                                        'is_auto_clock_out'
                                    ] ?? false
                                )
                                    <span
                                        class="employee-calendar-auto-out"
                                        title="Clock out diisi otomatis"
                                    >
                                        <i class="bi bi-box-arrow-right"></i>
                                    </span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    <div class="employee-calendar-legend mt-3">
                        @foreach ([
                            ['code' => 'OT', 'label' => 'Tepat Waktu'],
                            ['code' => 'LT', 'label' => 'Terlambat'],
                            ['code' => 'EL', 'label' => 'Terlambat Berizin'],
                            ['code' => 'HL', 'label' => 'Cuti/Izin Setengah Hari'],
                            ['code' => 'LV', 'label' => 'Cuti/Izin Hari Penuh'],
                            ['code' => 'AB', 'label' => 'Tidak Hadir'],
                            ['code' => 'MS', 'label' => 'Data Belum Lengkap'],
                            ['code' => 'OD', 'label' => 'Hari Libur Kerja'],
                            ['code' => 'PH', 'label' => 'Hari Libur'],
                            ['code' => 'PR', 'label' => 'Hadir'],
                        ] as $legend)
                            <div class="employee-calendar-legend-item">
                                <span class="employee-calendar-legend-code {{ $statusClass($legend['code']) }}">
                                    {{ $legend['code'] }}
                                </span>

                                <span>
                                    {{ $legend['label'] }}
                                </span>
                            </div>
                        @endforeach

                        <div class="employee-calendar-legend-item">
                            <span class="employee-calendar-auto-legend">
                                <i class="bi bi-box-arrow-right"></i>
                            </span>

                            <span>Clock out diisi otomatis</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">
                            Tren 6 Bulan
                        </h5>

                        <p class="content-card-subtitle mb-0">
                            Perubahan tingkat kehadiran, ketepatan waktu, dan ketidakhadiran employee.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="employee-trend-chart">
                        <canvas id="employeeAttendanceTrendChart"></canvas>
                    </div>

                    <div class="employee-trend-summary mt-3">
                        <div>
                            <span>Tingkat Kehadiran</span>
                            <strong>
                                {{ $formatPercent(
                                    $employeeOverview[
                                        'presence_rate'
                                    ] ?? 0
                                ) }}
                            </strong>
                        </div>

                        <div>
                            <span>Ketepatan Waktu</span>
                            <strong>
                                {{ $formatPercent(
                                    $employeeOverview[
                                        'on_time_rate'
                                    ] ?? 0
                                ) }}
                            </strong>
                        </div>

                        <div>
                            <span>Tingkat Ketidakhadiran</span>
                            <strong>
                                {{ $formatPercent(
                                    $employeeOverview[
                                        'absence_rate'
                                    ] ?? 0
                                ) }}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">
                    Catatan yang Perlu Diperhatikan
                </h5>

                <p class="content-card-subtitle mb-0">
                    Ringkasan tanggal dengan keterlambatan, tidak hadir, pulang lebih awal, atau clock out yang tidak tercatat.
                </p>
            </div>

            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                {{ $formatNumber($exceptionRows->count()) }}
                catatan
            </span>
        </div>

        <div class="content-card-body">
            <div class="employee-exception-list">
                @forelse ($exceptionRows as $exception)
                    @php
                        $parts = [];

                        if (
                            (int) (
                                $exception[
                                    'late_minutes'
                                ] ?? 0
                            ) > 0
                        ) {
                            $parts[] = number_format(
                                (int) $exception[
                                    'late_minutes'
                                ]
                            ) . ' menit terlambat';
                        }

                        if (
                            (int) (
                                $exception[
                                    'early_leave_minutes'
                                ] ?? 0
                            ) > 0
                        ) {
                            $parts[] = number_format(
                                (int) $exception[
                                    'early_leave_minutes'
                                ]
                            ) . ' menit pulang lebih awal';
                        }

                        if (
                            $exception[
                                'is_auto_clock_out'
                            ] ?? false
                        ) {
                            $parts[] =
                                'clock out tidak tercatat';
                        }

                        if (
                            ! empty(
                                $exception[
                                    'leave_type'
                                ]
                            )
                        ) {
                            $leaveType =
                                $leaveLabels[
                                    $exception[
                                        'leave_type'
                                    ]
                                ]
                                ?? \Illuminate\Support\Str::headline(
                                    $exception[
                                        'leave_type'
                                    ]
                                );

                            $leaveDuration =
                                $leaveDurationLabels[
                                    $exception[
                                        'leave_duration'
                                    ] ?? ''
                                ]
                                ?? null;

                            $parts[] = trim(
                                $leaveType
                                . (
                                    $leaveDuration
                                        ? ' · '
                                            . $leaveDuration
                                        : ''
                                )
                            );
                        }

                        $exceptionDescription = $parts
                            ? implode(' · ', $parts)
                            : (
                                $exception['label']
                                    ?? 'Ada catatan kehadiran yang perlu diperiksa'
                            );
                    @endphp

                    <article class="employee-exception-item">
                        <span class="employee-exception-icon {{ $statusClass($exception['code'] ?? '-') }}">
                            <i class="bi {{ $statusIcon($exception['code'] ?? '-') }}"></i>
                        </span>

                        <div class="employee-exception-content">
                            <div class="employee-exception-header">
                                <div>
                                    <div class="employee-exception-title">
                                        {{ $exception['label']
                                            ?? 'Catatan Kehadiran' }}
                                    </div>

                                    <div class="employee-exception-date">
                                        {{ $formatDate(
                                            $exception[
                                                'attendance_date'
                                            ] ?? null
                                        ) }}
                                    </div>
                                </div>

                                <span class="employee-exception-code {{ $statusClass($exception['code'] ?? '-') }}">
                                    {{ $exception['code'] ?? '-' }}
                                </span>
                            </div>

                            <div class="employee-exception-description">
                                {{ $exceptionDescription }}
                            </div>

                            @if (
                                filled(
                                    $exception['remarks'] ?? null
                                )
                            )
                                <div class="employee-exception-remarks">
                                    {{ $exception['remarks'] }}
                                </div>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="empty-state-box">
                        <div class="empty-state-icon">
                            <i class="bi bi-person-check-fill"></i>
                        </div>

                        <h5 class="empty-state-title">
                            Tidak ada catatan yang perlu diperhatikan
                        </h5>

                        <p class="empty-state-text mb-0">
                            Belum ada keterlambatan, ketidakhadiran, pulang lebih awal, atau clock out tidak tercatat pada periode ini.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div
    class="modal fade"
    id="employeeAttendanceDetailModal"
    tabindex="-1"
    aria-labelledby="employeeAttendanceDetailModalLabel"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content employee-attendance-modal">
            <div class="modal-header">
                <div>
                    <div class="modal-eyebrow">
                        Detail Kehadiran
                    </div>

                    <h5
                        class="modal-title"
                        id="employeeAttendanceDetailModalLabel"
                    >
                        Attendance Employee
                    </h5>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Tutup"
                ></button>
            </div>

            <div class="modal-body">
                <div class="employee-modal-status">
                    <span
                        class="employee-modal-status-code"
                        id="employeeModalStatusCode"
                    >
                        -
                    </span>

                    <div>
                        <span>Status Kehadiran</span>
                        <strong id="employeeModalStatusLabel">
                            Belum Ada Data Final
                        </strong>
                    </div>
                </div>

                <div class="employee-modal-date mt-3">
                    <i class="bi bi-calendar3"></i>

                    <div>
                        <span>Tanggal</span>
                        <strong id="employeeModalDate">
                            -
                        </strong>
                    </div>
                </div>

                <div class="employee-modal-grid mt-3">
                    <div>
                        <span>Jadwal Kerja</span>
                        <strong id="employeeModalSchedule">
                            -
                        </strong>
                    </div>

                    <div>
                        <span>Jam Terjadwal</span>
                        <strong id="employeeModalScheduledTime">
                            -
                        </strong>
                    </div>

                    <div>
                        <span>Clock In</span>
                        <strong id="employeeModalClockIn">
                            -
                        </strong>
                    </div>

                    <div>
                        <span>Clock Out</span>
                        <strong id="employeeModalClockOut">
                            -
                        </strong>
                    </div>

                    <div>
                        <span>Keterlambatan</span>
                        <strong id="employeeModalLate">
                            -
                        </strong>
                    </div>

                    <div>
                        <span>Cuti / Izin</span>
                        <strong id="employeeModalLeave">
                            -
                        </strong>
                    </div>

                    <div>
                        <span>Status Kedatangan</span>
                        <strong id="employeeModalArrival">
                            -
                        </strong>
                    </div>

                    <div>
                        <span>Status Kepulangan</span>
                        <strong id="employeeModalDeparture">
                            -
                        </strong>
                    </div>
                </div>

                <div
                    class="employee-modal-notice d-none mt-3"
                    id="employeeModalAutoClockOut"
                >
                    <i class="bi bi-info-circle-fill"></i>

                    <span>
                        Jam pulang tidak tercatat dan diisi otomatis berdasarkan jadwal kerja.
                    </span>
                </div>

                <div class="employee-modal-remarks mt-3">
                    <span>Catatan</span>

                    <p
                        class="mb-0"
                        id="employeeModalRemarks"
                    >
                        Tidak ada catatan.
                    </p>
                </div>
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-outline-secondary btn-modern"
                    data-bs-dismiss="modal"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .employee-attendance-detail-page {
        --employee-purple: #5B3E8E;
        --employee-purple-soft: #f2edf8;
        --employee-green: #3B8E4D;
        --employee-red: #c2414b;
        --employee-orange: #dc762a;
        --employee-blue: #2f6da5;
        --employee-text: #2f2938;
        --employee-muted: #756d80;
        --employee-border: #e8e3ed;
        --employee-surface-soft: #faf9fc;
    }

    .employee-attendance-detail-page .min-w-0 {
        min-width: 0;
    }

    .employee-profile-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.25rem;
        padding: 1.15rem;
        background: #ffffff;
        border: 1px solid var(--employee-border);
        border-radius: 1rem;
        box-shadow: 0 10px 28px rgba(49, 34, 72, .05);
    }

    .employee-profile-main {
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 0;
    }

    .employee-profile-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 4rem;
        height: 4rem;
        flex: 0 0 4rem;
        color: #ffffff;
        background: var(--employee-purple);
        border-radius: 1rem;
        font-size: 1.35rem;
        font-weight: 900;
    }

    .employee-profile-content {
        min-width: 0;
    }

    .employee-profile-name {
        color: var(--employee-text);
        font-size: 1.12rem;
        font-weight: 900;
        overflow-wrap: anywhere;
    }

    .employee-profile-meta {
        display: flex;
        align-items: center;
        gap: .65rem;
        margin-top: .45rem;
        flex-wrap: wrap;
    }

    .employee-profile-meta span {
        display: inline-flex;
        align-items: center;
        gap: .32rem;
        color: var(--employee-muted);
        font-size: .69rem;
        font-weight: 700;
    }

    .employee-profile-meta i {
        color: var(--employee-purple);
    }

    .employee-profile-schedule {
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        width: fit-content;
        margin-top: .7rem;
        padding: .6rem .7rem;
        background: var(--employee-surface-soft);
        border: 1px solid var(--employee-border);
        border-radius: .75rem;
    }

    .employee-profile-schedule > i {
        margin-top: .05rem;
        color: var(--employee-purple);
    }

    .employee-profile-schedule div {
        display: grid;
        gap: .08rem;
    }

    .employee-profile-schedule span {
        color: var(--employee-muted);
        font-size: .6rem;
        font-weight: 750;
    }

    .employee-profile-schedule strong {
        color: var(--employee-text);
        font-size: .71rem;
    }

    .employee-period-selector {
        display: grid;
        gap: .55rem;
        min-width: 280px;
    }

    .employee-period-selector > div:first-child {
        display: grid;
        gap: .1rem;
        text-align: right;
    }

    .employee-period-label {
        color: var(--employee-muted);
        font-size: .62rem;
        font-weight: 750;
    }

    .employee-period-selector strong {
        color: var(--employee-text);
        font-size: .82rem;
    }

    .employee-period-navigation {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: .45rem;
    }

    .employee-period-navigation .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2.7rem;
    }

    .employee-kpi-card {
        display: flex;
        flex-direction: column;
        min-height: 100%;
        padding: 1rem;
        background: #ffffff;
        border: 1px solid var(--employee-border);
        border-radius: 1rem;
        box-shadow: 0 8px 24px rgba(48, 34, 68, .045);
    }

    .employee-kpi-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.3rem;
        height: 2.3rem;
        color: var(--employee-purple);
        background: var(--employee-purple-soft);
        border-radius: .75rem;
        font-size: 1rem;
    }

    .employee-kpi-label {
        margin-top: .72rem;
        color: var(--employee-muted);
        font-size: .7rem;
        font-weight: 800;
        line-height: 1.3;
    }

    .employee-kpi-value {
        margin-top: .42rem;
        color: var(--employee-text);
        font-size: 1.5rem;
        font-weight: 900;
        line-height: 1;
    }

    .employee-kpi-help {
        margin-top: auto;
        padding-top: .72rem;
        color: var(--employee-muted);
        font-size: .67rem;
        line-height: 1.45;
    }

    .employee-calendar-weekdays,
    .employee-calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: .55rem;
    }

    .employee-calendar-weekdays {
        margin-bottom: .55rem;
    }

    .employee-calendar-weekdays div {
        color: var(--employee-muted);
        font-size: .62rem;
        font-weight: 850;
        text-align: center;
        text-transform: uppercase;
    }

    .employee-calendar-empty {
        min-height: 6.6rem;
    }

    .employee-calendar-day {
        position: relative;
        display: grid;
        align-content: start;
        gap: .32rem;
        min-height: 6.6rem;
        padding: .65rem;
        border: 1px solid transparent;
        border-radius: .8rem;
        text-align: left;
        cursor: pointer;
        transition:
            transform .16s ease,
            box-shadow .16s ease,
            border-color .16s ease;
    }

    .employee-calendar-day:hover {
        z-index: 2;
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(56, 38, 82, .12);
    }

    .employee-calendar-day.is-weekend {
        background-image:
            linear-gradient(
                rgba(255,255,255,.18),
                rgba(255,255,255,.18)
            );
    }

    .employee-calendar-date {
        color: currentColor;
        font-size: .66rem;
        font-weight: 900;
        opacity: .82;
    }

    .employee-calendar-status {
        display: inline-flex;
        align-items: center;
        gap: .28rem;
        font-size: .72rem;
        font-weight: 900;
    }

    .employee-calendar-label {
        display: -webkit-box;
        color: currentColor;
        font-size: .57rem;
        font-weight: 700;
        line-height: 1.35;
        opacity: .84;
        overflow: hidden;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .employee-calendar-auto-out {
        position: absolute;
        right: .38rem;
        bottom: .35rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.15rem;
        height: 1.15rem;
        color: #ffffff;
        background: #5d6670;
        border-radius: 50%;
        font-size: .58rem;
    }

    .employee-calendar-legend {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
        padding: .75rem;
        background: var(--employee-surface-soft);
        border: 1px solid var(--employee-border);
        border-radius: .8rem;
    }

    .employee-calendar-legend-item {
        display: inline-flex;
        align-items: center;
        gap: .32rem;
        color: var(--employee-muted);
        font-size: .62rem;
        font-weight: 700;
    }

    .employee-calendar-legend-code {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.85rem;
        height: 1.5rem;
        padding: 0 .3rem;
        border: 1px solid transparent;
        border-radius: .5rem;
        font-size: .57rem;
        font-weight: 900;
    }

    .employee-calendar-auto-legend {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.5rem;
        height: 1.5rem;
        color: #ffffff;
        background: #5d6670;
        border-radius: .5rem;
        font-size: .62rem;
    }

    .employee-trend-chart {
        position: relative;
        height: 300px;
    }

    .employee-trend-summary {
        display: grid;
        gap: .55rem;
    }

    .employee-trend-summary div {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .65rem .7rem;
        background: var(--employee-surface-soft);
        border: 1px solid var(--employee-border);
        border-radius: .72rem;
    }

    .employee-trend-summary span {
        color: var(--employee-muted);
        font-size: .65rem;
        font-weight: 700;
    }

    .employee-trend-summary strong {
        color: var(--employee-purple);
        font-size: .82rem;
    }

    .employee-exception-list {
        display: grid;
        gap: .75rem;
    }

    .employee-exception-item {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
        padding: .8rem;
        background: #ffffff;
        border: 1px solid var(--employee-border);
        border-radius: .85rem;
    }

    .employee-exception-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.4rem;
        height: 2.4rem;
        flex: 0 0 2.4rem;
        border: 1px solid transparent;
        border-radius: .75rem;
        font-size: .9rem;
    }

    .employee-exception-content {
        flex: 1 1 auto;
        min-width: 0;
    }

    .employee-exception-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
    }

    .employee-exception-title {
        color: var(--employee-text);
        font-size: .78rem;
        font-weight: 850;
    }

    .employee-exception-date {
        margin-top: .1rem;
        color: var(--employee-muted);
        font-size: .62rem;
    }

    .employee-exception-code {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2rem;
        height: 1.6rem;
        padding: 0 .35rem;
        border: 1px solid transparent;
        border-radius: .5rem;
        font-size: .6rem;
        font-weight: 900;
    }

    .employee-exception-description {
        margin-top: .5rem;
        color: #5f5767;
        font-size: .7rem;
        line-height: 1.45;
    }

    .employee-exception-remarks {
        margin-top: .5rem;
        padding: .55rem .65rem;
        color: var(--employee-muted);
        background: var(--employee-surface-soft);
        border-radius: .65rem;
        font-size: .66rem;
        line-height: 1.45;
    }

    .employee-attendance-modal {
        overflow: hidden;
        border: 0;
        border-radius: 1rem;
    }

    .employee-attendance-modal .modal-header {
        padding: 1rem 1.1rem;
        color: #ffffff;
        background: var(--employee-purple);
        border-bottom: 0;
    }

    .employee-attendance-modal .modal-header .btn-close {
        filter: invert(1);
    }

    .employee-attendance-modal .modal-eyebrow {
        color: rgba(255, 255, 255, .72);
        font-size: .64rem;
        font-weight: 850;
        letter-spacing: .07em;
        text-transform: uppercase;
    }

    .employee-attendance-modal .modal-title {
        margin-top: .12rem;
        font-size: .95rem;
        font-weight: 850;
    }

    .employee-modal-status,
    .employee-modal-date {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .72rem .78rem;
        background: var(--employee-surface-soft);
        border: 1px solid var(--employee-border);
        border-radius: .78rem;
    }

    .employee-modal-status-code {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2.1rem;
        height: 1.75rem;
        padding: 0 .35rem;
        border: 1px solid transparent;
        border-radius: .55rem;
        font-size: .63rem;
        font-weight: 900;
    }

    .employee-modal-status div,
    .employee-modal-date div {
        display: grid;
        gap: .08rem;
    }

    .employee-modal-status span,
    .employee-modal-date span,
    .employee-modal-grid span,
    .employee-modal-remarks > span {
        color: var(--employee-muted);
        font-size: .61rem;
        font-weight: 750;
    }

    .employee-modal-status strong,
    .employee-modal-date strong {
        color: var(--employee-text);
        font-size: .76rem;
    }

    .employee-modal-date > i {
        color: var(--employee-purple);
    }

    .employee-modal-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .6rem;
    }

    .employee-modal-grid div {
        display: grid;
        gap: .1rem;
        padding: .62rem .68rem;
        background: #ffffff;
        border: 1px solid var(--employee-border);
        border-radius: .7rem;
    }

    .employee-modal-grid strong {
        color: var(--employee-text);
        font-size: .73rem;
        overflow-wrap: anywhere;
    }

    .employee-modal-notice {
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        padding: .7rem .75rem;
        color: #4e5862;
        background: #f1f3f5;
        border: 1px solid #d9dde1;
        border-radius: .75rem;
        font-size: .68rem;
        line-height: 1.45;
    }

    .employee-modal-notice i {
        margin-top: .05rem;
    }

    .employee-modal-remarks {
        display: grid;
        gap: .3rem;
        padding: .7rem .75rem;
        background: var(--employee-surface-soft);
        border: 1px solid var(--employee-border);
        border-radius: .75rem;
    }

    .employee-modal-remarks p {
        color: var(--employee-text);
        font-size: .71rem;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .is-on-time {
        color: #2f7740;
        background: #edf8f0;
        border-color: #cbe3d1;
    }

    .is-late {
        color: #735600;
        background: #fff8df;
        border-color: #efdc99;
    }

    .is-excused-late {
        color: #5b3e8e;
        background: #f2edf8;
        border-color: #d9cdea;
    }

    .is-half-leave {
        color: #6d4a95;
        background: #f5effb;
        border-color: #dfd1ed;
    }

    .is-full-leave {
        color: #56357f;
        background: #eee6f6;
        border-color: #d5c4e6;
    }

    .is-absent {
        color: #a33b44;
        background: #fceff0;
        border-color: #efc9cd;
    }

    .is-missing {
        color: #9d4f14;
        background: #fff2e8;
        border-color: #efcfb6;
    }

    .is-off-day {
        color: #665f6c;
        background: #f1eff3;
        border-color: #ddd9e1;
    }

    .is-holiday {
        color: #245d8e;
        background: #edf5fa;
        border-color: #c9dfec;
    }

    .is-present {
        color: #2f7740;
        background: #f0f8f2;
        border-color: #cbe3d1;
    }

    .is-empty {
        color: #8b8493;
        background: #faf9fb;
        border-color: #e7e2ea;
    }

    @media (max-width: 1199.98px) {
        .employee-profile-card {
            align-items: flex-start;
            flex-direction: column;
        }

        .employee-period-selector {
            width: 100%;
            min-width: 0;
        }

        .employee-period-selector > div:first-child {
            text-align: left;
        }
    }

    @media (max-width: 991.98px) {
        .employee-calendar-day {
            min-height: 5.7rem;
            padding: .5rem;
        }

        .employee-calendar-label {
            font-size: .53rem;
        }

        .employee-trend-chart {
            height: 270px;
        }
    }

    @media (max-width: 767.98px) {
        .employee-attendance-detail-page {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        .employee-profile-main {
            align-items: flex-start;
        }

        .employee-profile-avatar {
            width: 3.4rem;
            height: 3.4rem;
            flex-basis: 3.4rem;
        }

        .employee-calendar-weekdays {
            display: none;
        }

        .employee-calendar-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .employee-calendar-empty {
            display: none;
        }

        .employee-calendar-day {
            min-height: 5.4rem;
        }

        .employee-modal-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 575.98px) {
        .employee-profile-main {
            flex-direction: column;
        }

        .employee-profile-meta {
            align-items: flex-start;
            flex-direction: column;
            gap: .35rem;
        }

        .employee-calendar-grid {
            grid-template-columns: 1fr;
        }

        .employee-calendar-day {
            min-height: auto;
        }

        .employee-calendar-label {
            -webkit-line-clamp: 1;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const monthInput = document.getElementById(
        'employeeMonth'
    );

    monthInput?.addEventListener('change', function () {
        document
            .getElementById(
                'employeeMonthFilterForm'
            )
            ?.submit();
    });

    function decodeAttendancePayload(encodedPayload) {
        if (!encodedPayload) {
            return null;
        }

        try {
            const binary = window.atob(encodedPayload);
            const bytes = Uint8Array.from(
                binary,
                function (character) {
                    return character.charCodeAt(0);
                }
            );

            return JSON.parse(
                new TextDecoder('utf-8').decode(bytes)
            );
        } catch (error) {
            console.error(
                'Detail attendance tidak dapat dibaca.',
                error
            );

            return null;
        }
    }

    function formatDate(value) {
        if (!value) {
            return '-';
        }

        const date = new Date(
            value + 'T00:00:00'
        );

        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return new Intl.DateTimeFormat(
            'id-ID',
            {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                weekday: 'long'
            }
        ).format(date);
    }

    function statusClass(code) {
        return {
            OT: 'is-on-time',
            LT: 'is-late',
            EL: 'is-excused-late',
            HL: 'is-half-leave',
            LV: 'is-full-leave',
            AB: 'is-absent',
            MS: 'is-missing',
            OD: 'is-off-day',
            PH: 'is-holiday',
            PR: 'is-present'
        }[code] || 'is-empty';
    }

    function humanize(value) {
        if (!value) {
            return '-';
        }

        return String(value)
            .replaceAll('_', ' ')
            .replace(
                /\b\w/g,
                function (character) {
                    return character.toUpperCase();
                }
            );
    }

    const leaveLabels = @json($leaveLabels);
    const leaveDurationLabels = @json(
        $leaveDurationLabels
    );

    const modalElement = document.getElementById(
        'employeeAttendanceDetailModal'
    );

    const attendanceModal = (
        modalElement
        && window.bootstrap?.Modal
    )
        ? window.bootstrap.Modal.getOrCreateInstance(
            modalElement
        )
        : null;

    document.addEventListener('click', function (event) {
        const button = event.target.closest(
            '[data-attendance-detail]'
        );

        if (!button) {
            return;
        }

        const payload = decodeAttendancePayload(
            button.dataset.attendanceDetail
        );

        if (!payload) {
            return;
        }

        const codeElement = document.getElementById(
            'employeeModalStatusCode'
        );

        if (codeElement) {
            codeElement.className =
                'employee-modal-status-code '
                + statusClass(payload.code);

            codeElement.textContent =
                payload.code || '-';
        }

        const scheduledTime = (
            payload.scheduled_start_time
            || payload.scheduled_end_time
        )
            ? (
                (payload.scheduled_start_time || '-')
                + '–'
                + (payload.scheduled_end_time || '-')
            )
            : '-';

        const leaveText = payload.leave_type
            ? (
                leaveLabels[payload.leave_type]
                || humanize(payload.leave_type)
            )
            + (
                payload.leave_duration
                    ? ' · '
                        + (
                            leaveDurationLabels[
                                payload.leave_duration
                            ]
                            || humanize(
                                payload.leave_duration
                            )
                        )
                    : ''
            )
            : '-';

        const values = {
            employeeModalStatusLabel:
                payload.label
                || 'Belum Ada Data Final',
            employeeModalDate:
                formatDate(payload.date),
            employeeModalSchedule:
                payload.template || '-',
            employeeModalScheduledTime:
                scheduledTime,
            employeeModalClockIn:
                payload.clock_in || '-',
            employeeModalClockOut:
                payload.clock_out || '-',
            employeeModalLate:
                Number(payload.late_minutes || 0) > 0
                    ? Number(
                        payload.late_minutes
                    ).toLocaleString('id-ID')
                        + ' menit'
                    : '-',
            employeeModalLeave:
                leaveText,
            employeeModalArrival:
                humanize(payload.arrival_status),
            employeeModalDeparture:
                humanize(payload.departure_status),
            employeeModalRemarks:
                payload.remarks
                || 'Tidak ada catatan.'
        };

        Object.entries(values).forEach(function (
            [elementId, value]
        ) {
            const element = document.getElementById(
                elementId
            );

            if (element) {
                element.textContent = value;
            }
        });

        document
            .getElementById(
                'employeeModalAutoClockOut'
            )
            ?.classList.toggle(
                'd-none',
                !Boolean(
                    payload.is_auto_clock_out
                )
            );

        attendanceModal?.show();
    });

    if (typeof Chart === 'undefined') {
        return;
    }

    Chart.defaults.font.family = getComputedStyle(
        document.body
    ).fontFamily;

    Chart.defaults.color = '#756d80';

    const trendData = @json($monthlyTrend ?? []);

    const trendCanvas = document.getElementById(
        'employeeAttendanceTrendChart'
    );

    if (trendCanvas) {
        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: trendData.labels || [],
                datasets: [
                    {
                        label: 'Tingkat Kehadiran',
                        data:
                            trendData
                                .datasets
                                ?.presence_rate
                            || [],
                        borderColor: '#5B3E8E',
                        backgroundColor:
                            'rgba(91, 62, 142, .08)',
                        borderWidth: 2.5,
                        tension: .35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        fill: false
                    },
                    {
                        label: 'Ketepatan Waktu',
                        data:
                            trendData
                                .datasets
                                ?.on_time_rate
                            || [],
                        borderColor: '#3B8E4D',
                        backgroundColor:
                            'rgba(59, 142, 77, .08)',
                        borderWidth: 2.5,
                        tension: .35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        fill: false
                    },
                    {
                        label: 'Ketidakhadiran',
                        data:
                            trendData
                                .datasets
                                ?.absence_rate
                            || [],
                        borderColor: '#C2414B',
                        backgroundColor:
                            'rgba(194, 65, 75, .08)',
                        borderWidth: 2.5,
                        tension: .35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 14,
                            font: {
                                size: 10,
                                weight: 700
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return ' '
                                    + context.dataset.label
                                    + ': '
                                    + Number(
                                        context.raw || 0
                                    ).toFixed(1)
                                    + '%';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 9,
                                weight: 700
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: 100,
                        ticks: {
                            callback: function (value) {
                                return value + '%';
                            },
                            font: {
                                size: 9
                            }
                        },
                        grid: {
                            color:
                                'rgba(117, 109, 128, .09)'
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
