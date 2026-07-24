@php
    $groupEmployeeName = $employeeGroup['employee_name']
        ?? 'Unknown Employee';

    $groupEmployeeNumber = $employeeGroup['employee_number']
        ?: 'No employee number';

    $groupReviewCount = (int) (
        $employeeGroup['needs_review_count'] ?? 0
    );

    $groupErrorCount = (int) (
        $employeeGroup['error_count'] ?? 0
    );

    $groupDuplicateCount = (int) (
        $employeeGroup['duplicate_count'] ?? 0
    );

    $groupDateFrom = $employeeGroup['date_from'] ?? null;
    $groupDateTo = $employeeGroup['date_to'] ?? null;

    $groupRows = ($employeeGroup['rows'] ?? null)
        instanceof \Illuminate\Support\Collection
            ? $employeeGroup['rows']
            : collect($employeeGroup['rows'] ?? []);

    $groupHolidayCount = $groupRows
        ->where('attendance_type', 'holiday')
        ->count();

    $groupAutoClockOutCount = $groupRows
        ->filter(
            fn ($groupRow) => (bool) data_get(
                $groupRow,
                'raw_payload._system.auto_clock_out',
                false
            )
        )
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Stable Async Group Key
    |--------------------------------------------------------------------------
    | Controller menghasilkan key seperti:
    | - employee-12
    | - unmatched-{md5}
    |
    | Key mentah disimpan pada data attribute untuk request async.
    | Prefix attendance-group- hanya digunakan sebagai DOM id.
    */
    $groupKey = (string) (
        $employeeGroup['key']
        ?? (
            'unmatched-'
            . md5(
                mb_strtolower(
                    trim($groupEmployeeName)
                )
            )
        )
    );

    $safeGroupKey = preg_replace(
        '/[^A-Za-z0-9\-_:.]/',
        '-',
        $groupKey
    );

    $groupDomKey = 'attendance-group-' . $safeGroupKey;
@endphp

<section
    class="employee-attendance-group"
    id="{{ $groupDomKey }}"
    data-attendance-group-key="{{ $groupKey }}"
    data-attendance-group-dom-id="{{ $groupDomKey }}"
    data-attendance-record-count="{{ $employeeGroup['record_count'] ?? $groupRows->count() }}"
>
    <div class="employee-group-header">
        <div class="employee-group-identity">
            <span class="employee-group-icon">
                <i class="bi bi-person-badge"></i>
            </span>

            <div class="min-w-0">
                <div class="employee-group-name">
                    {{ $groupEmployeeName }}
                </div>

                <div class="employee-group-meta">
                    {{ $groupEmployeeNumber }}
                    · {{ number_format($employeeGroup['record_count'] ?? $groupRows->count()) }} records

                    @if ($groupDateFrom && $groupDateTo)
                        · {{ $groupDateFrom->format('d M') }}
                        – {{ $groupDateTo->format('d M Y') }}
                    @endif
                </div>
            </div>
        </div>

        <div class="employee-group-badges">
            @if ($employeeGroup['is_unmatched'] ?? false)
                <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis border border-danger-subtle">
                    Unmatched Employee
                </span>
            @endif

            @if ($groupReviewCount > 0)
                <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                    {{ $groupReviewCount }} Needs Review
                </span>
            @endif

            @if ($groupErrorCount > 0)
                <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis border border-danger-subtle">
                    {{ $groupErrorCount }} Error
                </span>
            @endif

            @if ($groupDuplicateCount > 0)
                <span class="badge rounded-pill bg-dark-subtle text-dark-emphasis border border-dark-subtle">
                    {{ $groupDuplicateCount }} Duplicate
                </span>
            @endif

            @if ($groupAutoClockOutCount > 0)
                <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                    {{ $groupAutoClockOutCount }} Auto Clock-out
                </span>
            @endif

            @if ($groupHolidayCount > 0)
                <span class="badge rounded-pill bg-info-subtle text-info-emphasis border border-info-subtle">
                    {{ $groupHolidayCount }} Holiday
                </span>
            @endif
        </div>
    </div>

    <div class="attendance-record-list">
        @foreach ($groupRows as $row)
            @include(
                'hr.attendance-imports.partials.attendance-card',
                [
                    'row' => $row,
                    'attendanceImport' => $attendanceImport,
                    'canEdit' => $canEdit,
                    'groupKey' => $groupKey,
                    'groupDomKey' => $groupDomKey,
                    'attendanceTypeOptions' => $attendanceTypeOptions,
                    'punctualityOptions' => $punctualityOptions,
                    'reviewStatusOptions' => $reviewStatusOptions,
                    'sourceOptions' => $sourceOptions,
                    'leaveTypeOptions' => $leaveTypeOptions,
                    'leaveDurationOptions' => $leaveDurationOptions,
                    'leaveSessionOptions' => $leaveSessionOptions,
                    'highlightRowId' => $highlightRowId ?? null,
                ]
            )
        @endforeach
    </div>
</section>
