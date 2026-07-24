@php
    /*
    |--------------------------------------------------------------------------
    | Self-contained Display Helpers
    |--------------------------------------------------------------------------
    | Partial ini juga aman bila suatu saat dirender secara langsung.
    */
    $reviewBadgeClass = $reviewBadgeClass
        ?? function ($status) {
            return match($status) {
                'valid' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
                'needs_review' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                'resolved' => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
                'ignored' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                'error' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
                'duplicate' => 'bg-dark-subtle text-dark-emphasis border border-dark-subtle',
                default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
            };
        };

    $attendanceBadgeClass = $attendanceBadgeClass
        ?? function ($status) {
            return match($status) {
                'present' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
                'absent' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
                'missing' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                'off_day' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                'holiday' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                default => 'bg-dark-subtle text-dark-emphasis border border-dark-subtle',
            };
        };

    $formatTime = $formatTime
        ?? fn ($time) => filled($time)
            ? substr((string) $time, 0, 5)
            : '-';

    $editPayload = [
        'id' => $row->id,
        'group_key' => $groupKey,
        'group_dom_key' => $groupDomKey,
        'employee_id' => $row->employee_id,
        'working_hour_template_id' => $row->working_hour_template_id,
        'attendance_date' => optional(
            $row->attendance_date
        )->format('Y-m-d'),
        'clock_in' => $row->clock_in,
        'clock_out' => $row->clock_out,
        'scheduled_start_time' => $row->scheduled_start_time,
        'scheduled_end_time' => $row->scheduled_end_time,
        'attendance_type' => $row->attendance_type,
        'punctuality_status' => $row->punctuality_status,
        'arrival_status' => $row->arrival_status,
        'departure_status' => $row->departure_status,
        'late_minutes' => $row->late_minutes,
        'early_leave_minutes' => $row->early_leave_minutes,
        'leave_type' => $row->leave_type,
        'leave_duration' => $row->leave_duration,
        'leave_session' => $row->leave_session,
        'leave_start_time' => $row->leave_start_time,
        'leave_end_time' => $row->leave_end_time,
        'leave_minutes' => $row->leave_minutes,
        'is_excused' => (bool) $row->is_excused,
        'leave_reason' => $row->leave_reason,
        'remarks' => $row->remarks,
        'review_status' => $row->review_status,
        'update_url' => route(
            'hr.attendance-imports.rows.update',
            [$attendanceImport, $row]
        ),
    ];

    $encodedEditPayload = base64_encode(
        json_encode(
            $editPayload,
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
        )
    );

    $isNeedsReview = $canEdit
        && $row->review_status === 'needs_review';

    $isAutoClockOut = (bool) data_get(
        $row,
        'raw_payload._system.auto_clock_out',
        false
    );

    $isHighlighted = (int) ($highlightRowId ?? 0)
        === (int) $row->id;

    $recordStateClass = match (true) {
        $row->attendance_type === 'holiday' => 'is-holiday',
        $row->attendance_type === 'off_day' => 'is-off-day',
        $row->review_status === 'needs_review' => 'is-needs-review',
        $row->review_status === 'error' => 'is-error',
        $row->review_status === 'duplicate' => 'is-duplicate',
        default => '',
    };

    $templateName = $row->workingHourTemplate?->name
        ?? $row->working_hours_template_raw
        ?? 'Unknown Template';

    $attendanceLabel = $attendanceTypeOptions[
        $row->attendance_type
    ]
        ?? ucfirst(
            str_replace(
                '_',
                ' ',
                (string) $row->attendance_type
            )
        );

    $punctualityLabel = $punctualityOptions[
        $row->punctuality_status
    ]
        ?? ucfirst(
            str_replace(
                '_',
                ' ',
                (string) $row->punctuality_status
            )
        );

    $reviewLabel = $reviewStatusOptions[
        $row->review_status
    ]
        ?? ucfirst(
            str_replace(
                '_',
                ' ',
                (string) $row->review_status
            )
        );

    $sourceLabel = $sourceOptions[$row->source]
        ?? ucfirst(
            str_replace(
                '_',
                ' ',
                (string) $row->source
            )
        );
@endphp

<article
    id="attendance-record-{{ $row->id }}"
    class="attendance-record-card
        {{ $recordStateClass }}
        {{ $isNeedsReview ? 'needs-review-row' : '' }}
        {{ $isHighlighted ? 'is-recently-updated' : '' }}"
    data-attendance-row-id="{{ $row->id }}"
    data-attendance-group-key="{{ $groupKey }}"
    data-attendance-group-dom-id="{{ $groupDomKey }}"
    @if ($isNeedsReview)
        role="button"
        tabindex="0"
        title="Click to review this attendance record"
        data-attendance-payload="{{ $encodedEditPayload }}"
    @endif
>
    <div class="attendance-record-main">
        <div class="attendance-select-cell">
            @if ($canEdit)
                <input
                    type="checkbox"
                    class="form-check-input row-checkbox"
                    name="row_ids[]"
                    value="{{ $row->id }}"
                    aria-label="Select attendance row for {{ $row->attendance_date?->format('d M Y') ?? 'unknown date' }}"
                >
            @endif
        </div>

        <div class="attendance-data-cell attendance-date-cell">
            <div class="attendance-data-label">
                <i class="bi bi-calendar3"></i>Date
            </div>

            <div class="attendance-data-value">
                {{ $row->attendance_date?->format('d M Y') ?? '-' }}
            </div>

            <div class="attendance-data-help">
                {{ $row->attendance_date?->format('l') ?? 'Date unavailable' }}
            </div>
        </div>

        <div class="attendance-data-cell attendance-schedule-cell">
            <div class="attendance-data-label">
                <i class="bi bi-clock-history"></i>Schedule
            </div>

            <div class="attendance-data-value">
                {{ $templateName }}
            </div>

            <div class="attendance-data-help">
                {{ $formatTime($row->scheduled_start_time) }}
                –
                {{ $formatTime($row->scheduled_end_time) }}
            </div>

            @if ($row->schedule_is_inferred)
                <span class="attendance-auto-badge">
                    <i class="bi bi-stars"></i>Inferred schedule
                </span>
            @endif
        </div>

        <div class="attendance-data-cell attendance-actual-cell">
            <div class="attendance-data-label">
                <i class="bi bi-fingerprint"></i>Actual Time
            </div>

            @if (
                in_array(
                    $row->attendance_type,
                    ['holiday', 'off_day'],
                    true
                )
            )
                <div class="attendance-no-clock">
                    No clock required
                </div>
            @else
                <div class="attendance-clock-pair">
                    <div class="attendance-clock-box">
                        <div class="attendance-clock-label">
                            Clock In
                        </div>

                        <div class="attendance-clock-value">
                            {{ $formatTime($row->clock_in) }}
                        </div>
                    </div>

                    <div class="attendance-clock-box">
                        <div class="attendance-clock-label">
                            Clock Out
                        </div>

                        <div class="attendance-clock-value">
                            {{ $formatTime($row->clock_out) }}
                        </div>
                    </div>
                </div>

                @if ($isAutoClockOut)
                    <span class="attendance-auto-badge">
                        <i class="bi bi-magic"></i>Auto clock-out
                    </span>
                @endif
            @endif
        </div>

        <div class="attendance-data-cell attendance-status-cell">
            <div class="attendance-data-label">
                <i class="bi bi-person-check"></i>Attendance
            </div>

            <div class="attendance-badge-stack">
                <span class="badge rounded-pill {{ $attendanceBadgeClass($row->attendance_type) }}">
                    {{ $attendanceLabel }}
                </span>

                <span class="badge rounded-pill bg-light text-dark border">
                    {{ $punctualityLabel }}
                </span>
            </div>

            @if ($row->late_minutes)
                <div class="attendance-data-help">
                    {{ number_format($row->late_minutes) }} min late
                </div>
            @elseif ($row->early_leave_minutes)
                <div class="attendance-data-help">
                    {{ number_format($row->early_leave_minutes) }} min early
                </div>
            @endif
        </div>

        <div class="attendance-data-cell attendance-review-cell">
            <div class="attendance-data-label">
                <i class="bi bi-shield-check"></i>Review
            </div>

            <div class="attendance-badge-stack">
                <span class="badge rounded-pill {{ $reviewBadgeClass($row->review_status) }}">
                    {{ $reviewLabel }}
                </span>
            </div>

            @if (
                $row->validation_message
                && in_array(
                    $row->review_status,
                    ['needs_review', 'error', 'duplicate'],
                    true
                )
            )
                <div class="attendance-validation {{ $row->review_status === 'error' ? 'is-error' : '' }}">
                    <i class="bi bi-exclamation-circle-fill mt-1"></i>
                    <span>{{ $row->validation_message }}</span>
                </div>
            @endif

            @if ($isNeedsReview)
                <div class="review-click-hint">
                    <i class="bi bi-cursor"></i>
                    Click card to review
                </div>
            @endif
        </div>
    </div>

    <div class="attendance-record-meta">
        <div class="attendance-data-cell attendance-leave-cell">
            <div class="attendance-data-label">
                <i class="bi bi-calendar2-minus"></i>Leave / Permission
            </div>

            @if ($row->leave_type)
                <div class="attendance-data-value">
                    {{ $leaveTypeOptions[$row->leave_type]
                        ?? ucfirst(str_replace('_', ' ', $row->leave_type)) }}
                </div>

                <div class="attendance-data-help">
                    {{ $leaveDurationOptions[$row->leave_duration]
                        ?? ucfirst(str_replace('_', ' ', (string) $row->leave_duration)) }}

                    @if ($row->leave_session)
                        · {{ $leaveSessionOptions[$row->leave_session]
                            ?? ucfirst(str_replace('_', ' ', $row->leave_session)) }}
                    @endif
                </div>

                @if ($row->is_excused)
                    <span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle mt-2">
                        Excused
                    </span>
                @endif
            @else
                <div class="attendance-data-help mt-0">
                    No leave or permission recorded.
                </div>
            @endif
        </div>

        <div class="attendance-data-cell attendance-source-cell">
            <div class="attendance-data-label">
                <i class="bi bi-database"></i>Source
            </div>

            <span class="badge rounded-pill bg-light text-dark border">
                {{ $sourceLabel }}
            </span>

            @if ($isAutoClockOut)
                <div class="attendance-data-help">
                    Clock-out generated from scheduled end time.
                </div>
            @elseif ($row->schedule_is_inferred)
                <div class="attendance-data-help">
                    Schedule resolved from employee or system defaults.
                </div>
            @endif
        </div>

        <div class="attendance-data-cell attendance-notes-cell">
            <div class="attendance-data-label">
                <i class="bi bi-card-text"></i>Remarks
            </div>

            <div class="attendance-notes-box">
                {{ $row->remarks ?: 'No remarks.' }}
            </div>
        </div>

        <div class="attendance-action-cell">
            @if ($canEdit)
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary px-3 edit-row-button"
                    data-attendance-payload="{{ $encodedEditPayload }}"
                >
                    <i class="bi bi-pencil-square me-2"></i>
                    {{ $row->review_status === 'needs_review' ? 'Review' : 'Edit' }}
                </button>
            @else
                <span class="badge rounded-pill bg-light text-muted border">
                    <i class="bi bi-lock me-1"></i>Locked
                </span>
            @endif
        </div>
    </div>
</article>
