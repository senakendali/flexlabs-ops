@php
    $employeeGroups = $employeeGroups instanceof \Illuminate\Support\Collection
        ? $employeeGroups
        : collect($employeeGroups ?? []);
@endphp

@if ($employeeGroups->isNotEmpty())
    <div
        class="employee-group-list"
        id="attendanceEmployeeGroupList"
        data-attendance-group-count="{{ $employeeGroups->count() }}"
        data-attendance-row-count="{{ $employeeGroups->sum('record_count') }}"
    >
        @foreach ($employeeGroups as $employeeGroup)
            @include(
                'hr.attendance-imports.partials.employee-group',
                [
                    'employeeGroup' => $employeeGroup,
                    'attendanceImport' => $attendanceImport,
                    'canEdit' => $canEdit,
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
@else
    <div
        class="empty-state-box"
        id="attendanceReviewEmptyState"
    >
        <div class="empty-state-icon">
            <i class="bi bi-calendar2-check"></i>
        </div>

        <h5 class="empty-state-title">
            No attendance row found
        </h5>

        <p class="empty-state-text mb-0">
            No attendance row matches the current filters.
        </p>
    </div>
@endif
