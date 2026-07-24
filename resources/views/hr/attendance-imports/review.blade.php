@extends('layouts.app-dashboard')

@section('title', 'Attendance Import Review')

@section('content')
@php
    $reviewBadgeClass = function ($status) {
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

    $attendanceBadgeClass = function ($status) {
        return match($status) {
            'present' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
            'absent' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
            'missing' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'off_day' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
            'holiday' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
            default => 'bg-dark-subtle text-dark-emphasis border border-dark-subtle',
        };
    };

    $statusDotClass = function ($status) {
        return match($status) {
            'reviewing' => 'bg-warning',
            'processing' => 'bg-info',
            'completed' => 'bg-success',
            'failed' => 'bg-danger',
            'cancelled' => 'bg-dark',
            default => 'bg-secondary',
        };
    };

    $canConfirm = isset($canConfirm)
        ? (bool) $canConfirm
        : (
            $canEdit
            && (int) $attendanceImport->review_rows === 0
            && (int) $attendanceImport->error_rows === 0
            && (int) $attendanceImport->duplicate_rows === 0
        );

    $formatTime = fn ($time) => filled($time)
        ? substr((string) $time, 0, 5)
        : '-';

@endphp

<style>
    .attendance-review-page {
        --attendance-purple: #5B3E8E;
        --attendance-purple-dark: #493173;
        --attendance-yellow: #FFBE04;
        --attendance-border: #e8e3f0;
        --attendance-soft-purple: #f7f4fb;
    }

    /*
    |--------------------------------------------------------------------------
    | Purple Header Compatibility
    |--------------------------------------------------------------------------
    */
    .attendance-review-page .page-header-card .page-eyebrow,
    .attendance-review-page .page-header-card .page-title {
        color: #fff !important;
    }

    .attendance-review-page .page-header-card .page-subtitle,
    .attendance-review-page .page-header-card .header-meta,
    .attendance-review-page .page-header-card .header-meta span {
        color: rgba(255, 255, 255, .78) !important;
    }

    .attendance-review-page .header-status-badge {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .48rem .75rem;
        color: #fff;
        background: rgba(255, 255, 255, .14);
        border: 1px solid rgba(255, 255, 255, .26);
        backdrop-filter: blur(8px);
    }

    .attendance-review-page .header-status-dot {
        width: .55rem;
        height: .55rem;
        display: inline-block;
        border-radius: 50%;
        box-shadow: 0 0 0 3px rgba(255, 255, 255, .16);
    }

    .attendance-review-page .header-btn-back {
        color: var(--attendance-purple) !important;
        background: #fff !important;
        border-color: #fff !important;
    }

    .attendance-review-page .header-btn-back:hover {
        color: var(--attendance-purple-dark) !important;
        background: #f7f5fb !important;
        border-color: #f7f5fb !important;
    }

    .attendance-review-page .header-btn-cancel {
        color: #fff !important;
        background: rgba(255, 255, 255, .08) !important;
        border-color: rgba(255, 255, 255, .55) !important;
    }

    .attendance-review-page .header-btn-cancel:hover {
        color: #fff !important;
        background: rgba(255, 255, 255, .18) !important;
        border-color: #fff !important;
    }

    .attendance-review-page .header-btn-confirm {
        color: #2d2340 !important;
        background: var(--attendance-yellow) !important;
        border-color: var(--attendance-yellow) !important;
    }

    .attendance-review-page .header-btn-confirm:hover {
        color: #21172f !important;
        background: #f3b500 !important;
        border-color: #f3b500 !important;
    }

    .attendance-review-page .header-btn-confirm:disabled {
        color: rgba(255, 255, 255, .65) !important;
        background: rgba(255, 255, 255, .15) !important;
        border-color: rgba(255, 255, 255, .25) !important;
        opacity: 1;
    }

    /*
    |--------------------------------------------------------------------------
    | Filter Layout
    |--------------------------------------------------------------------------
    */
    .attendance-review-page .filter-actions {
        border-top: 1px solid var(--attendance-border);
        padding-top: 1rem;
        margin-top: 1rem;
    }

    /*
    |--------------------------------------------------------------------------
    | Grouped Attendance Cards
    |--------------------------------------------------------------------------
    | The review page intentionally uses responsive cards instead of a wide
    | table. Every value remains visible without horizontal scrolling.
    */
    .attendance-review-page .attendance-review-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .9rem 1rem;
        margin-bottom: 1rem;
        border: 1px solid var(--attendance-border);
        border-radius: 1rem;
        background: #faf9fc;
    }

    .attendance-review-page .attendance-select-all {
        display: inline-flex;
        align-items: center;
        gap: .6rem;
        min-width: 0;
    }

    .attendance-review-page .attendance-select-all .form-check-input {
        margin-top: 0;
        flex: 0 0 auto;
    }

    .attendance-review-page .attendance-filter-status {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        min-height: 31px;
        padding: .35rem .65rem;
        color: #6f657c;
        background: #f8f6fb;
        border: 1px solid var(--attendance-border);
        border-radius: 999px;
        font-size: .76rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .attendance-review-page .attendance-filter-status.is-loading {
        color: var(--attendance-purple);
        background: #f1ebf8;
        border-color: #d8cbe8;
    }

    .attendance-review-page .attendance-async-shell {
        position: relative;
        min-height: 180px;
    }

    .attendance-review-page .attendance-review-content {
        min-width: 0;
        transition:
            opacity .16s ease,
            filter .16s ease;
    }

    .attendance-review-page .attendance-async-shell.is-loading
    .attendance-review-content {
        opacity: .42;
        filter: saturate(.78);
        pointer-events: none;
        user-select: none;
    }

    .attendance-review-page .attendance-review-loading {
        position: absolute;
        inset: 0;
        z-index: 18;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding-top: 2.5rem;
        pointer-events: none;
    }

    .attendance-review-page .attendance-review-loading-panel {
        display: inline-flex;
        align-items: center;
        gap: .75rem;
        max-width: calc(100% - 2rem);
        padding: .75rem 1rem;
        color: var(--attendance-purple);
        background: rgba(255, 255, 255, .96);
        border: 1px solid #d9cce9;
        border-radius: 1rem;
        box-shadow: 0 14px 34px rgba(54, 36, 82, .14);
        font-size: .86rem;
        font-weight: 800;
    }

    .attendance-review-page .attendance-review-loading-panel .spinner-border {
        width: 1rem;
        height: 1rem;
        border-width: .14em;
    }

    .attendance-review-page .attendance-record-card.is-recently-updated {
        border-color: #86c99a;
        background: #f3fbf5;
        box-shadow:
            inset 4px 0 0 #3b8e4d,
            0 10px 26px rgba(59, 142, 77, .12);
        animation: attendanceUpdatedPulse 1.1s ease;
    }

    @keyframes attendanceUpdatedPulse {
        0% {
            transform: scale(.995);
        }

        45% {
            transform: scale(1.004);
        }

        100% {
            transform: scale(1);
        }
    }

    .attendance-review-page .employee-group-list {
        display: grid;
        gap: 1.25rem;
        min-width: 0;
    }

    .attendance-review-page .employee-attendance-group {
        min-width: 0;
        overflow: hidden;
        border: 1px solid var(--attendance-border);
        border-radius: 1.1rem;
        background: #fff;
        box-shadow: 0 8px 24px rgba(54, 36, 82, .045);
    }

    .attendance-review-page .employee-group-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        padding: 1rem 1.1rem;
        background: var(--attendance-soft-purple);
        border-bottom: 1px solid #ded5e9;
    }

    .attendance-review-page .employee-group-identity {
        display: flex;
        align-items: center;
        gap: .8rem;
        min-width: 0;
    }

    .attendance-review-page .employee-group-icon {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 42px;
        color: var(--attendance-purple);
        background: #ece5f6;
        border-radius: 13px;
    }

    .attendance-review-page .employee-group-name {
        color: #171322;
        font-size: 1rem;
        font-weight: 800;
        line-height: 1.3;
        overflow-wrap: anywhere;
    }

    .attendance-review-page .employee-group-meta {
        color: #746b82;
        font-size: .82rem;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .attendance-review-page .employee-group-badges {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .45rem;
        flex-wrap: wrap;
    }

    .attendance-review-page .attendance-record-list {
        display: grid;
        gap: .75rem;
        padding: .9rem;
        min-width: 0;
        background: #fcfbfd;
    }

    .attendance-review-page .attendance-record-card {
        position: relative;
        min-width: 0;
        padding: 1rem;
        border: 1px solid #e8e5ec;
        border-radius: 1rem;
        background: #fff;
        transition:
            transform .16s ease,
            border-color .16s ease,
            box-shadow .16s ease,
            background-color .16s ease;
    }

    .attendance-review-page .attendance-record-card.is-needs-review {
        border-color: #efd57a;
        background: #fffdf3;
        box-shadow: inset 4px 0 0 #e9b800;
    }

    .attendance-review-page .attendance-record-card.is-error {
        border-color: #f4b9b4;
        background: #fff9f8;
        box-shadow: inset 4px 0 0 #d92d20;
    }

    .attendance-review-page .attendance-record-card.is-duplicate {
        border-color: #cbd1d8;
        background: #fafbfc;
        box-shadow: inset 4px 0 0 #475467;
    }

    .attendance-review-page .attendance-record-card.is-holiday {
        border-color: #b8e0ed;
        background: #f4fbfd;
        box-shadow: inset 4px 0 0 #2996b3;
    }

    .attendance-review-page .attendance-record-card.is-off-day {
        border-color: #d9dde3;
        background: #f8f9fb;
        box-shadow: inset 4px 0 0 #98a2b3;
    }

    .attendance-review-page .attendance-record-card.needs-review-row {
        cursor: pointer;
    }

    .attendance-review-page .attendance-record-card.needs-review-row:hover {
        transform: translateY(-1px);
        border-color: #d9b935;
        box-shadow:
            inset 4px 0 0 #e9b800,
            0 10px 24px rgba(89, 68, 8, .08);
    }

    .attendance-review-page .attendance-record-card.needs-review-row:focus-visible {
        outline: 3px solid rgba(91, 62, 142, .24);
        outline-offset: 2px;
    }

    .attendance-review-page .attendance-record-main,
    .attendance-review-page .attendance-record-meta {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: .85rem 1rem;
        min-width: 0;
    }

    .attendance-review-page .attendance-record-main {
        align-items: start;
    }

    .attendance-review-page .attendance-record-meta {
        align-items: start;
        padding-top: 1rem;
        padding-bottom: .35rem;
        margin-top: 1rem;
        border-top: 1px solid #eeeaf2;
    }

    .attendance-review-page .attendance-select-cell {
        grid-column: span 1;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding-top: .15rem;
    }

    .attendance-review-page .attendance-date-cell {
        grid-column: span 2;
    }

    .attendance-review-page .attendance-schedule-cell {
        grid-column: span 3;
    }

    .attendance-review-page .attendance-actual-cell {
        grid-column: span 2;
    }

    .attendance-review-page .attendance-status-cell {
        grid-column: span 2;
    }

    .attendance-review-page .attendance-review-cell {
        grid-column: span 2;
    }

    .attendance-review-page .attendance-leave-cell {
        grid-column: span 3;
    }

    .attendance-review-page .attendance-source-cell {
        grid-column: span 2;
    }

    .attendance-review-page .attendance-notes-cell {
        grid-column: span 5;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .attendance-review-page .attendance-action-cell {
        grid-column: span 2;
        display: flex;
        align-items: flex-end;
        justify-content: flex-end;
    }

    .attendance-review-page .attendance-data-cell {
        min-width: 0;
    }

    .attendance-review-page .attendance-data-label {
        display: flex;
        align-items: center;
        gap: .4rem;
        margin-bottom: .35rem;
        color: #7d748a;
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .055em;
        line-height: 1.25;
        text-transform: uppercase;
    }

    .attendance-review-page .attendance-data-value {
        color: #18141f;
        font-size: .92rem;
        font-weight: 750;
        line-height: 1.45;
        overflow-wrap: anywhere;
    }

    .attendance-review-page .attendance-data-help {
        color: #777080;
        font-size: .79rem;
        line-height: 1.5;
        margin-top: .18rem;
        overflow-wrap: anywhere;
    }

    .attendance-review-page .attendance-clock-pair {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .5rem;
    }

    .attendance-review-page .attendance-clock-box {
        min-width: 0;
        padding: .55rem .65rem;
        border: 1px solid #ebe7ef;
        border-radius: .75rem;
        background: #faf9fc;
    }

    .attendance-review-page .attendance-clock-label {
        color: #7f778a;
        font-size: .68rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .attendance-review-page .attendance-clock-value {
        color: #191520;
        font-size: .92rem;
        font-weight: 800;
        margin-top: .15rem;
    }

    .attendance-review-page .attendance-badge-stack {
        display: flex;
        align-items: flex-start;
        gap: .4rem;
        flex-wrap: wrap;
    }

    .attendance-review-page .attendance-validation {
        display: flex;
        align-items: flex-start;
        gap: .55rem;
        padding: .68rem .75rem;
        margin-top: .6rem;
        color: #9a6700;
        background: #fff7d6;
        border: 1px solid #f1d982;
        border-radius: .75rem;
        font-size: .79rem;
        font-weight: 650;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .attendance-review-page .attendance-validation.is-error {
        color: #a32119;
        background: #fff0ee;
        border-color: #f3bbb6;
    }

    .attendance-review-page .attendance-notes-box {
        width: 100%;
        min-height: 76px;
        height: auto;
        padding: .75rem .85rem;
        margin-bottom: .25rem;
        color: #5e5668;
        background: #faf9fc;
        border: 1px solid #ebe7ef;
        border-radius: .75rem;
        font-size: .82rem;
        line-height: 1.55;
        overflow-wrap: anywhere;
        white-space: pre-line;
    }

    .attendance-review-page .attendance-record-card.is-recently-updated {
        border-color: #86c99a;
        background: #f3fbf5;
        box-shadow:
            inset 4px 0 0 #3B8E4D,
            0 10px 26px rgba(59, 142, 77, .12);
    }

    .attendance-review-page .attendance-no-clock {
        color: #687080;
        font-size: .82rem;
        font-weight: 650;
        padding: .55rem .65rem;
        border-radius: .75rem;
        background: #f4f6f8;
    }

    .attendance-review-page .review-click-hint {
        display: flex;
        align-items: center;
        gap: .35rem;
        color: #866500;
        font-size: .75rem;
        font-weight: 700;
        margin-top: .5rem;
    }

    .attendance-review-page .attendance-auto-badge {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        margin-top: .35rem;
        padding: .24rem .48rem;
        color: #5B3E8E;
        background: #eee8f7;
        border: 1px solid #d7cae8;
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 800;
    }

    .attendance-review-page .bulk-action-bar {
        position: sticky;
        bottom: 1rem;
        z-index: 20;
        padding: .9rem 1rem;
        margin-top: 1rem;
        color: #fff;
        background: rgba(73, 49, 115, .96);
        border: 1px solid rgba(255, 255, 255, .14);
        border-radius: 1rem;
        box-shadow: 0 14px 36px rgba(40, 24, 64, .24);
        backdrop-filter: blur(12px);
    }

    .attendance-review-page .bulk-action-bar .text-muted {
        color: rgba(255, 255, 255, .7) !important;
    }

    /*
    |--------------------------------------------------------------------------
    | Scrollable Modal Content
    |--------------------------------------------------------------------------
    */
    #editAttendanceModal .attendance-modal-dialog,
    #bulkUpdateModal .attendance-modal-dialog {
        max-height: calc(100vh - 2rem);
        max-height: calc(100dvh - 2rem);
        margin-top: 1rem;
        margin-bottom: 1rem;
    }

    #editAttendanceModal .attendance-modal-form {
        display: flex;
        width: 100%;
        max-height: calc(100vh - 2rem);
        max-height: calc(100dvh - 2rem);
    }

    #editAttendanceModal .attendance-modal-dialog .modal-content,
    #bulkUpdateModal .attendance-modal-dialog .modal-content {
        display: flex;
        flex-direction: column;
        width: 100%;
        max-height: calc(100vh - 2rem);
        max-height: calc(100dvh - 2rem);
        overflow: hidden;
    }

    #editAttendanceModal .modal-header,
    #editAttendanceModal .modal-footer,
    #bulkUpdateModal .modal-header,
    #bulkUpdateModal .modal-footer {
        flex: 0 0 auto;
        background: #fff;
        position: relative;
        z-index: 2;
    }

    #editAttendanceModal .modal-body,
    #bulkUpdateModal .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        scrollbar-width: thin;
        scrollbar-color: #b8a9cf #f4f1f8;
    }

    #editAttendanceModal .modal-body::-webkit-scrollbar,
    #bulkUpdateModal .modal-body::-webkit-scrollbar {
        width: 10px;
    }

    #editAttendanceModal .modal-body::-webkit-scrollbar-track,
    #bulkUpdateModal .modal-body::-webkit-scrollbar-track {
        background: #f4f1f8;
        border-radius: 999px;
    }

    #editAttendanceModal .modal-body::-webkit-scrollbar-thumb,
    #bulkUpdateModal .modal-body::-webkit-scrollbar-thumb {
        background: #b8a9cf;
        border: 2px solid #f4f1f8;
        border-radius: 999px;
    }

    @media (max-width: 1199.98px) {
        .attendance-review-page .attendance-record-main,
        .attendance-review-page .attendance-record-meta {
            grid-template-columns: repeat(8, minmax(0, 1fr));
        }

        .attendance-review-page .attendance-select-cell {
            grid-column: span 1;
        }

        .attendance-review-page .attendance-date-cell {
            grid-column: span 3;
        }

        .attendance-review-page .attendance-schedule-cell {
            grid-column: span 4;
        }

        .attendance-review-page .attendance-actual-cell {
            grid-column: span 3;
        }

        .attendance-review-page .attendance-status-cell {
            grid-column: span 2;
        }

        .attendance-review-page .attendance-review-cell {
            grid-column: span 3;
        }

        .attendance-review-page .attendance-leave-cell {
            grid-column: span 3;
        }

        .attendance-review-page .attendance-source-cell {
            grid-column: span 2;
        }

        .attendance-review-page .attendance-notes-cell {
            grid-column: span 3;
        }

        .attendance-review-page .attendance-action-cell {
            grid-column: 1 / -1;
            justify-content: flex-start;
        }
    }

    @media (max-width: 767.98px) {
        .attendance-review-page .attendance-review-toolbar,
        .attendance-review-page .employee-group-header {
            align-items: flex-start;
        }

        .attendance-review-page .attendance-review-toolbar {
            flex-direction: column;
        }

        .attendance-review-page .employee-group-badges {
            justify-content: flex-start;
        }

        .attendance-review-page .attendance-record-main,
        .attendance-review-page .attendance-record-meta {
            grid-template-columns: 1fr;
        }

        .attendance-review-page .attendance-select-cell,
        .attendance-review-page .attendance-date-cell,
        .attendance-review-page .attendance-schedule-cell,
        .attendance-review-page .attendance-actual-cell,
        .attendance-review-page .attendance-status-cell,
        .attendance-review-page .attendance-review-cell,
        .attendance-review-page .attendance-leave-cell,
        .attendance-review-page .attendance-source-cell,
        .attendance-review-page .attendance-notes-cell,
        .attendance-review-page .attendance-action-cell {
            grid-column: 1;
        }

        .attendance-review-page .attendance-select-cell {
            justify-content: flex-start;
            padding-top: 0;
        }

        .attendance-review-page .attendance-action-cell .btn {
            width: 100%;
        }

        .attendance-review-page .attendance-clock-pair {
            grid-template-columns: 1fr 1fr;
        }

        .attendance-review-page .bulk-action-bar {
            bottom: .5rem;
        }

        #editAttendanceModal .attendance-modal-dialog,
        #bulkUpdateModal .attendance-modal-dialog {
            max-height: calc(100vh - 1rem);
            max-height: calc(100dvh - 1rem);
            margin: .5rem;
        }

        #editAttendanceModal .attendance-modal-form,
        #editAttendanceModal .attendance-modal-dialog .modal-content,
        #bulkUpdateModal .attendance-modal-dialog .modal-content {
            max-height: calc(100vh - 1rem);
            max-height: calc(100dvh - 1rem);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Confirmation Dialog
    |--------------------------------------------------------------------------
    */
    #actionConfirmationModal .confirmation-icon {
        width: 52px;
        height: 52px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 52px;
        border-radius: 16px;
        font-size: 1.35rem;
    }

    #actionConfirmationModal .confirmation-icon.is-primary {
        color: var(--attendance-purple);
        background: #eee8f7;
    }

    #actionConfirmationModal .confirmation-icon.is-danger {
        color: #b42318;
        background: #fee4e2;
    }
</style>

<div class="container-fluid px-4 py-4 attendance-review-page">
    <div
        id="attendanceAsyncToastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 1095;"
        aria-live="polite"
        aria-atomic="true"
    ></div>

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                    <div class="page-eyebrow mb-0">Human Resources</div>

                    <span class="badge rounded-pill header-status-badge">
                        <span class="header-status-dot {{ $statusDotClass($attendanceImport->status) }}"></span>
                        {{ ucfirst($attendanceImport->status) }}
                    </span>
                </div>

                <h1 class="page-title mb-2">Attendance Import Review</h1>
                <p class="page-subtitle mb-2">{{ $attendanceImport->original_file_name }}</p>

                <div class="header-meta small d-flex gap-3 flex-wrap">
                    <span>
                        <i class="bi bi-table me-1"></i>
                        {{ $attendanceImport->sheet_name ?: 'Attendance' }}
                    </span>

                    <span>
                        <i class="bi bi-calendar-range me-1"></i>
                        @if ($attendanceImport->date_from && $attendanceImport->date_to)
                            {{ $attendanceImport->date_from->format('d M Y') }}
                            –
                            {{ $attendanceImport->date_to->format('d M Y') }}
                        @else
                            Period not detected
                        @endif
                    </span>

                    <span>
                        <i class="bi bi-person me-1"></i>
                        {{ $attendanceImport->uploader?->name ?? 'System' }}
                    </span>
                </div>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a
                    href="{{ route('hr.attendance-imports.index') }}"
                    class="btn btn-modern header-btn-back"
                >
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>

                @if ($canEdit)
                    <form
                        method="POST"
                        action="{{ route('hr.attendance-imports.cancel', $attendanceImport) }}"
                        id="cancelImportForm"
                        class="d-none"
                    >
                        @csrf
                        @method('PATCH')
                    </form>

                    <button
                        type="button"
                        class="btn btn-light btn-modern header-btn-cancel"
                        data-confirm-form="cancelImportForm"
                        data-confirm-title="Cancel Attendance Import"
                        data-confirm-message="This import will be cancelled and can no longer be finalized. Continue?"
                        data-confirm-label="Cancel Import"
                        data-confirm-variant="danger"
                    >
                        <i class="bi bi-x-circle me-2"></i>Cancel Import
                    </button>

                    <form
                        method="POST"
                        action="{{ route('hr.attendance-imports.confirm', $attendanceImport) }}"
                        id="confirmImportForm"
                        class="d-none"
                    >
                        @csrf
                    </form>

                    <button
                        type="button"
                        id="attendanceConfirmButton"
                        class="btn btn-modern header-btn-confirm"
                        data-confirm-form="confirmImportForm"
                        data-confirm-title="Confirm Attendance Import"
                        data-confirm-message="All reviewed rows will be saved as final employee attendance. Continue?"
                        data-confirm-label="Confirm Import"
                        data-confirm-variant="primary"
                        {{ $canConfirm ? '' : 'disabled' }}
                        title="{{ $canConfirm ? 'Confirm attendance import' : 'Resolve all review, error, and duplicate rows first.' }}"
                    >
                        <i class="bi bi-check-circle me-2"></i>Confirm Import
                    </button>
                @endif
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <div class="fw-semibold mb-2">Changes could not be saved.</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mb-4">
        @foreach ([
            ['key' => 'total_rows', 'label' => 'Total Rows', 'value' => $attendanceImport->total_rows, 'class' => 'text-dark'],
            ['key' => 'imported_rows', 'label' => 'Imported', 'value' => $attendanceImport->imported_rows, 'class' => 'text-dark'],
            ['key' => 'generated_rows', 'label' => 'Generated', 'value' => $attendanceImport->generated_rows, 'class' => 'text-dark'],
            ['key' => 'review_rows', 'label' => 'Needs Review', 'value' => $attendanceImport->review_rows, 'class' => 'text-warning'],
            ['key' => 'error_rows', 'label' => 'Error', 'value' => $attendanceImport->error_rows, 'class' => 'text-danger'],
            ['key' => 'duplicate_rows', 'label' => 'Duplicate', 'value' => $attendanceImport->duplicate_rows, 'class' => 'text-dark'],
        ] as $stat)
            <div class="col-6 col-md-4 col-xl-2">
                <div class="content-card h-100">
                    <div class="content-card-body">
                        <div class="small text-muted mb-2">{{ $stat['label'] }}</div>
                        <div
                            class="fs-3 fw-bold {{ $stat['class'] }}"
                            data-attendance-stat="{{ $stat['key'] }}"
                        >
                            {{ number_format($stat['value']) }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Attendance Filters</h5>
                <p class="content-card-subtitle mb-0">
                    Filter rows by employee, attendance type, review status, and date.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form
                method="GET"
                action="{{ route('hr.attendance-imports.review', $attendanceImport) }}"
                id="reviewFilterForm"
            >
                <div class="row g-3">
                    <div class="col-12 col-xl-3">
                        <label for="search" class="form-label">Search</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="{{ $filters['search'] ?? '' }}"
                            class="form-control"
                            placeholder="Employee, number, remarks..."
                        >
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <label for="employee_id" class="form-label">Employee</label>
                        <select name="employee_id" id="employee_id" class="form-select filter-auto-submit">
                            <option value="">All Employee</option>
                            @foreach ($employees as $employee)
                                <option
                                    value="{{ $employee->id }}"
                                    {{ (int) ($filters['employee_id'] ?? 0) === $employee->id ? 'selected' : '' }}
                                >
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <label for="attendance_type" class="form-label">Attendance</label>
                        <select name="attendance_type" id="attendance_type" class="form-select filter-auto-submit">
                            <option value="">All Type</option>
                            @foreach ($attendanceTypeOptions as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    {{ ($filters['attendance_type'] ?? '') === $value ? 'selected' : '' }}
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <label for="review_status" class="form-label">Review</label>
                        <select name="review_status" id="review_status" class="form-select filter-auto-submit">
                            <option value="">All Status</option>
                            @foreach ($reviewStatusOptions as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    {{ ($filters['review_status'] ?? '') === $value ? 'selected' : '' }}
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <label for="date_from" class="form-label">Date From</label>
                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            value="{{ $filters['date_from'] ?? '' }}"
                            class="form-control filter-auto-submit"
                        >
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <label for="date_to" class="form-label">Date To</label>
                        <input
                            type="date"
                            id="date_to"
                            name="date_to"
                            value="{{ $filters['date_to'] ?? '' }}"
                            class="form-control filter-auto-submit"
                        >
                    </div>
                </div>

                <div class="filter-actions d-flex justify-content-end align-items-center gap-2 flex-wrap">
                    <a
                        href="{{ route('hr.attendance-imports.review', $attendanceImport) }}"
                        class="btn btn-outline-secondary btn-modern"
                        id="resetReviewFilters"
                    >
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                    </a>

                    <span
                        class="attendance-filter-status"
                        id="attendanceDataStatus"
                        role="status"
                        aria-live="polite"
                    >
                        <i class="bi bi-check-circle"></i>
                        <span>Ready</span>
                    </span>

                    <button
                        type="submit"
                        class="btn btn-primary btn-modern"
                        id="reviewFilterSubmitButton"
                    >
                        <span class="default-text">
                            <i class="bi bi-search me-2"></i>Search
                        </span>

                        <span class="loading-text d-none">
                            <span
                                class="spinner-border spinner-border-sm me-2"
                                aria-hidden="true"
                            ></span>
                            Loading...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Attendance Review by Employee</h5>
                <p class="content-card-subtitle mb-0">
                    All employees and attendance rows are displayed in one page. Click a Needs Review row to open the adjustment form.
                </p>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span
                    class="badge rounded-pill bg-light text-dark border"
                    id="attendanceEmployeeCount"
                >
                    {{ number_format($employeeGroups->count()) }} employees
                </span>

                <span
                    class="badge rounded-pill bg-light text-dark border"
                    id="attendanceVisibleRowCount"
                >
                    {{ number_format($employeeGroups->sum('record_count')) }} attendance rows
                </span>
            </div>
        </div>

        <div class="content-card-body">
            <form
                method="POST"
                action="{{ route('hr.attendance-imports.bulk-update', $attendanceImport) }}"
                id="bulkUpdateForm"
            >
                @csrf
                @method('PATCH')

                @if ($canEdit)
                    <div
                        class="attendance-review-toolbar {{ $employeeGroups->sum('record_count') > 0 ? '' : 'd-none' }}"
                        id="attendanceReviewToolbar"
                    >
                        <label class="attendance-select-all mb-0" for="selectAllRows">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="selectAllRows"
                            >

                            <span>
                                <span class="fw-semibold text-dark d-block">
                                    Select all visible rows
                                </span>

                                <span class="small text-muted">
                                    Use selection only when the same adjustment applies to multiple records.
                                </span>
                            </span>
                        </label>

                        <span
                            class="badge rounded-pill bg-light text-dark border"
                            id="attendanceToolbarVisibleCount"
                        >
                            {{ number_format($employeeGroups->sum('record_count')) }}
                            visible records
                        </span>
                    </div>
                @endif

                <div
                    class="attendance-async-shell"
                    id="attendanceAsyncShell"
                >
                    <div
                        class="attendance-review-loading d-none"
                        id="attendanceReviewLoading"
                        aria-hidden="true"
                    >
                        <div class="attendance-review-loading-panel">
                            <span
                                class="spinner-border"
                                aria-hidden="true"
                            ></span>

                            <span id="attendanceReviewLoadingText">
                                Loading attendance data...
                            </span>
                        </div>
                    </div>

                    <div
                        class="attendance-review-content"
                        id="attendanceReviewContent"
                        data-review-url="{{ $reviewDataUrl ?? route('hr.attendance-imports.review-data', $attendanceImport) }}"
                        aria-live="polite"
                        aria-busy="false"
                    >
                        @include(
                            'hr.attendance-imports.partials.employee-groups',
                            [
                                'employeeGroups' => $employeeGroups,
                                'attendanceImport' => $attendanceImport,
                                'canEdit' => $canEdit,
                                'attendanceTypeOptions' => $attendanceTypeOptions,
                                'punctualityOptions' => $punctualityOptions,
                                'reviewStatusOptions' => $reviewStatusOptions,
                                'sourceOptions' => $sourceOptions,
                                'leaveTypeOptions' => $leaveTypeOptions,
                                'leaveDurationOptions' => $leaveDurationOptions,
                                'leaveSessionOptions' => $leaveSessionOptions,
                                'highlightRowId' => null,
                            ]
                        )
                    </div>
                </div>

                @if ($canEdit)
                    <div
                        class="bulk-action-bar d-flex justify-content-between align-items-center gap-3 flex-wrap d-none"
                        id="bulkActionBar"
                    >
                        <div>
                            <div class="fw-semibold">
                                <span id="selectedRowCount">0</span>
                                rows selected
                            </div>

                            <div class="small text-muted">
                                Apply one attendance resolution to the selected records.
                            </div>
                        </div>

                        <button
                            type="button"
                            class="btn btn-warning btn-modern"
                            id="openBulkUpdateButton"
                            data-bs-toggle="modal"
                            data-bs-target="#bulkUpdateModal"
                        >
                            <i class="bi bi-sliders me-2"></i>Bulk Adjust
                        </button>
                    </div>
                @endif
            </form>

        </div>
    </div>
</div>

{{-- Edit Attendance Modal --}}
<div class="modal fade" id="editAttendanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable attendance-modal-dialog">
        <form method="POST" id="editAttendanceForm" class="attendance-modal-form">
            @csrf
            @method('PATCH')

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-1">Edit Attendance Row</h5>
                        <div class="small text-muted">
                            Adjust employee, work schedule, attendance status, leave, permission, and review status.
                        </div>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div
                        id="editAttendanceFormAlert"
                        class="alert alert-danger d-none mb-3"
                        role="alert"
                    ></div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Employee & Date</h5>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-12 col-lg-5">
                                    <label class="form-label">Employee</label>
                                    <select name="employee_id" class="form-select" data-field="employee_id">
                                        <option value="">Select Employee</option>
                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->id }}">
                                                {{ $employee->name }} · {{ $employee->employee_number }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-lg-4">
                                    <label class="form-label">Working Template</label>
                                    <select name="working_hour_template_id" class="form-select" data-field="working_hour_template_id">
                                        <option value="">Select Template</option>
                                        @foreach ($workingHourTemplates as $template)
                                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-lg-3">
                                    <label class="form-label">Attendance Date</label>
                                    <input type="date" name="attendance_date" class="form-control" data-field="attendance_date">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Schedule & Actual Time</h5>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-6 col-lg-3">
                                    <label class="form-label">Scheduled Start</label>
                                    <input type="time" name="scheduled_start_time" class="form-control" data-field="scheduled_start_time">
                                </div>

                                <div class="col-6 col-lg-3">
                                    <label class="form-label">Scheduled End</label>
                                    <input type="time" name="scheduled_end_time" class="form-control" data-field="scheduled_end_time">
                                </div>

                                <div class="col-6 col-lg-3">
                                    <label class="form-label">Clock In</label>
                                    <input type="time" name="clock_in" class="form-control" data-field="clock_in">
                                </div>

                                <div class="col-6 col-lg-3">
                                    <label class="form-label">Clock Out</label>
                                    <input type="time" name="clock_out" class="form-control" data-field="clock_out">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Attendance Classification</h5>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Attendance Type</label>
                                    <select name="attendance_type" class="form-select" data-field="attendance_type">
                                        @foreach ($attendanceTypeOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label">Punctuality</label>
                                    <select name="punctuality_status" class="form-select" data-field="punctuality_status">
                                        @foreach ($punctualityOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label">Review Status</label>
                                    <select name="review_status" class="form-select" data-field="review_status">
                                        @foreach ($reviewStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label">Arrival Status</label>
                                    <select name="arrival_status" class="form-select" data-field="arrival_status">
                                        @foreach ($arrivalStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label">Departure Status</label>
                                    <select name="departure_status" class="form-select" data-field="departure_status">
                                        @foreach ($departureStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-6 col-md-2">
                                    <label class="form-label">Late Minutes</label>
                                    <input type="number" min="0" name="late_minutes" class="form-control" data-field="late_minutes">
                                </div>

                                <div class="col-6 col-md-2">
                                    <label class="form-label">Early Minutes</label>
                                    <input type="number" min="0" name="early_leave_minutes" class="form-control" data-field="early_leave_minutes">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Leave / Permission</h5>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Leave Type</label>
                                    <select name="leave_type" class="form-select" data-field="leave_type">
                                        <option value="">No Leave</option>
                                        @foreach ($leaveTypeOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label">Duration</label>
                                    <select name="leave_duration" class="form-select" data-field="leave_duration">
                                        <option value="">Select Duration</option>
                                        @foreach ($leaveDurationOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label">Session</label>
                                    <select name="leave_session" class="form-select" data-field="leave_session">
                                        <option value="">Select Session</option>
                                        @foreach ($leaveSessionOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-6 col-md-3">
                                    <label class="form-label">Leave Start</label>
                                    <input type="time" name="leave_start_time" class="form-control" data-field="leave_start_time">
                                </div>

                                <div class="col-6 col-md-3">
                                    <label class="form-label">Leave End</label>
                                    <input type="time" name="leave_end_time" class="form-control" data-field="leave_end_time">
                                </div>

                                <div class="col-6 col-md-3">
                                    <label class="form-label">Leave Minutes</label>
                                    <input type="number" min="0" name="leave_minutes" class="form-control" data-field="leave_minutes">
                                </div>

                                <div class="col-6 col-md-3 d-flex align-items-end">
                                    <div class="form-check form-switch mb-2">
                                        <input type="hidden" name="is_excused" value="0">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="is_excused"
                                            value="1"
                                            id="editIsExcused"
                                            data-field="is_excused"
                                        >
                                        <label class="form-check-label" for="editIsExcused">Excused</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Leave Reason</label>
                                    <textarea name="leave_reason" rows="2" class="form-control" data-field="leave_reason"></textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Remarks</label>
                                    <textarea name="remarks" rows="3" class="form-control" data-field="remarks"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary btn-modern"
                        id="saveAttendanceChangesButton"
                    >
                        <span class="default-text">
                            <i class="bi bi-check-circle me-2"></i>Save Changes
                        </span>
                        <span class="loading-text d-none">
                            <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Bulk Adjustment Modal --}}
<div class="modal fade" id="bulkUpdateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable attendance-modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-1">Bulk Attendance Adjustment</h5>
                    <div class="small text-muted">
                        Apply the same attendance resolution to all selected rows.
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div
                    id="bulkUpdateFormAlert"
                    class="alert alert-danger d-none mb-3"
                    role="alert"
                ></div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Attendance Type</label>
                        <select name="resolution[attendance_type]" class="form-select" form="bulkUpdateForm" required>
                            @foreach ($attendanceTypeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Review Status</label>
                        <select name="resolution[review_status]" class="form-select" form="bulkUpdateForm">
                            <option value="resolved">Resolved</option>
                            <option value="ignored">Ignored</option>
                            <option value="valid">Valid</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">Leave Type</label>
                        <select name="resolution[leave_type]" class="form-select" form="bulkUpdateForm">
                            <option value="">No Leave</option>
                            @foreach ($leaveTypeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">Duration</label>
                        <select name="resolution[leave_duration]" class="form-select" form="bulkUpdateForm">
                            <option value="">Select Duration</option>
                            @foreach ($leaveDurationOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">Session</label>
                        <select name="resolution[leave_session]" class="form-select" form="bulkUpdateForm">
                            <option value="">Select Session</option>
                            @foreach ($leaveSessionOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="hidden" name="resolution[is_excused]" value="0" form="bulkUpdateForm">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="resolution[is_excused]"
                                value="1"
                                id="bulkIsExcused"
                                form="bulkUpdateForm"
                            >
                            <label class="form-check-label" for="bulkIsExcused">
                                Mark as excused
                            </label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Leave Reason</label>
                        <textarea
                            name="resolution[leave_reason]"
                            rows="2"
                            class="form-control"
                            form="bulkUpdateForm"
                        ></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Remarks</label>
                        <textarea
                            name="resolution[remarks]"
                            rows="3"
                            class="form-control"
                            form="bulkUpdateForm"
                        ></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn btn-primary btn-modern"
                    id="bulkUpdateSubmitButton"
                    form="bulkUpdateForm"
                >
                    <span class="default-text">
                        <i class="bi bi-check2-all me-2"></i>
                        Apply to Selected Rows
                    </span>

                    <span class="loading-text d-none">
                        <span
                            class="spinner-border spinner-border-sm me-2"
                            aria-hidden="true"
                        ></span>
                        Applying...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Reusable Confirmation Dialog --}}
<div class="modal fade" id="actionConfirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body pt-2 pb-4 px-4">
                <div class="d-flex gap-3 align-items-start">
                    <div class="confirmation-icon is-primary" id="confirmationIcon">
                        <i class="bi bi-question-lg" id="confirmationIconGlyph"></i>
                    </div>

                    <div>
                        <h5 class="fw-bold mb-2" id="confirmationTitle">Confirm Action</h5>
                        <p class="text-muted mb-0" id="confirmationMessage">
                            Are you sure you want to continue?
                        </p>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Go Back
                </button>

                <button type="button" class="btn btn-primary btn-modern" id="confirmationSubmitButton">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let attendanceEditModal = null;
    let attendanceBulkModal = null;
    let attendanceConfirmationModal = null;
    let pendingAttendanceConfirmationForm = null;
    let attendanceDataAbortController = null;
    let attendanceSearchTimer = null;
    let attendanceDataRequestSequence = 0;

    const attendanceReviewPageUrl = @js(
        route(
            'hr.attendance-imports.review',
            $attendanceImport
        )
    );

    const attendanceReviewDataUrl = @js(
        $reviewDataUrl
            ?? route(
                'hr.attendance-imports.review-data',
                $attendanceImport
            )
    );

    function decodeAttendancePayload(encodedPayload) {
        if (!encodedPayload) {
            return null;
        }

        try {
            const binary = window.atob(encodedPayload);
            const bytes = Uint8Array.from(
                binary,
                character => character.charCodeAt(0)
            );

            return JSON.parse(
                new TextDecoder('utf-8').decode(bytes)
            );
        } catch (error) {
            console.error(
                'Attendance payload could not be decoded.',
                error
            );

            return null;
        }
    }

    function escapeAttendanceSelector(value) {
        if (window.CSS?.escape) {
            return window.CSS.escape(String(value));
        }

        return String(value).replace(
            /[^A-Za-z0-9_-]/g,
            character => `\\${character}`
        );
    }

    function getAttendanceEditModal() {
        const element = document.getElementById(
            'editAttendanceModal'
        );

        if (!element || !window.bootstrap?.Modal) {
            return null;
        }

        attendanceEditModal ??=
            window.bootstrap.Modal.getOrCreateInstance(
                element
            );

        return attendanceEditModal;
    }

    function getAttendanceBulkModal() {
        const element = document.getElementById(
            'bulkUpdateModal'
        );

        if (!element || !window.bootstrap?.Modal) {
            return null;
        }

        attendanceBulkModal ??=
            window.bootstrap.Modal.getOrCreateInstance(
                element
            );

        return attendanceBulkModal;
    }

    function getAttendanceConfirmationModal() {
        const element = document.getElementById(
            'actionConfirmationModal'
        );

        if (!element || !window.bootstrap?.Modal) {
            return null;
        }

        attendanceConfirmationModal ??=
            window.bootstrap.Modal.getOrCreateInstance(
                element
            );

        return attendanceConfirmationModal;
    }

    async function parseAttendanceResponse(response) {
        const contentType =
            response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
            return response.json();
        }

        const responseText = await response.text();

        return {
            success: response.ok,
            message: response.ok
                ? 'Request completed.'
                : 'Server returned an invalid response.',
            raw: responseText,
        };
    }

    function getAttendanceFilterForm() {
        return document.getElementById(
            'reviewFilterForm'
        );
    }

    function attendanceFilterParams() {
        const form = getAttendanceFilterForm();
        const params = new URLSearchParams();

        if (!form) {
            return params;
        }

        new FormData(form).forEach((value, key) => {
            const normalizedValue = String(value).trim();

            if (normalizedValue !== '') {
                params.append(key, normalizedValue);
            }
        });

        return params;
    }

    function attendanceDataRequestUrl({
        groupKeys = [],
        highlightRowId = null,
    } = {}) {
        const url = new URL(
            attendanceReviewDataUrl,
            window.location.origin
        );

        attendanceFilterParams().forEach(
            (value, key) => url.searchParams.append(
                key,
                value
            )
        );

        groupKeys.forEach(groupKey => {
            url.searchParams.append(
                'group_keys[]',
                groupKey
            );
        });

        if (highlightRowId) {
            url.searchParams.set(
                'highlight_row_id',
                String(highlightRowId)
            );
        }

        url.searchParams.set(
            '_async',
            String(Date.now())
        );

        return url;
    }

    function attendancePageUrlFromFilters() {
        const url = new URL(
            attendanceReviewPageUrl,
            window.location.origin
        );

        attendanceFilterParams().forEach(
            (value, key) => url.searchParams.append(
                key,
                value
            )
        );

        return url;
    }

    function updateAttendanceHistory(mode = 'none') {
        if (!['push', 'replace'].includes(mode)) {
            return;
        }

        const url = attendancePageUrlFromFilters();

        window.history[
            mode === 'push'
                ? 'pushState'
                : 'replaceState'
        ](
            {
                attendanceReview: true,
            },
            '',
            url
        );
    }

    function syncAttendanceFilterFormFromUrl() {
        const form = getAttendanceFilterForm();

        if (!form) {
            return;
        }

        const params = new URLSearchParams(
            window.location.search
        );

        Array.from(form.elements).forEach(field => {
            if (!field.name) {
                return;
            }

            field.value = params.get(field.name) || '';
        });
    }

    function clearAttendanceFilters() {
        const form = getAttendanceFilterForm();

        if (!form) {
            return;
        }

        Array.from(form.elements).forEach(field => {
            if (!field.name) {
                return;
            }

            if (
                field instanceof HTMLInputElement
                || field instanceof HTMLSelectElement
                || field instanceof HTMLTextAreaElement
            ) {
                field.value = '';
            }
        });
    }

    function setAttendanceDataStatus(
        message,
        loading = false
    ) {
        const status = document.getElementById(
            'attendanceDataStatus'
        );

        if (!status) {
            return;
        }

        status.classList.toggle(
            'is-loading',
            loading
        );

        const icon = status.querySelector('i');
        const label = status.querySelector('span');

        if (icon) {
            icon.className = loading
                ? 'bi bi-arrow-repeat'
                : 'bi bi-check-circle';
        }

        if (label) {
            label.textContent = message;
        }
    }

    function setAttendanceDataLoading(
        loading,
        message = 'Loading attendance data...'
    ) {
        const shell = document.getElementById(
            'attendanceAsyncShell'
        );
        const loadingElement = document.getElementById(
            'attendanceReviewLoading'
        );
        const loadingText = document.getElementById(
            'attendanceReviewLoadingText'
        );
        const content = document.getElementById(
            'attendanceReviewContent'
        );
        const filterButton = document.getElementById(
            'reviewFilterSubmitButton'
        );

        shell?.classList.toggle(
            'is-loading',
            loading
        );

        loadingElement?.classList.toggle(
            'd-none',
            !loading
        );

        loadingElement?.setAttribute(
            'aria-hidden',
            loading ? 'false' : 'true'
        );

        content?.setAttribute(
            'aria-busy',
            loading ? 'true' : 'false'
        );

        if (loadingText) {
            loadingText.textContent = message;
        }

        if (filterButton) {
            filterButton.disabled = loading;

            filterButton
                .querySelector('.default-text')
                ?.classList.toggle(
                    'd-none',
                    loading
                );

            filterButton
                .querySelector('.loading-text')
                ?.classList.toggle(
                    'd-none',
                    !loading
                );
        }

        if (loading) {
            setAttendanceDataStatus(
                'Refreshing...',
                true
            );
        }
    }

    function formatAttendanceCount(value) {
        return new Intl.NumberFormat('en-US').format(
            Number(value || 0)
        );
    }

    function updateAttendanceSummary(
        summary = {},
        canConfirm = false
    ) {
        document
            .querySelectorAll('[data-attendance-stat]')
            .forEach(element => {
                const key = element.dataset.attendanceStat;

                if (
                    Object.prototype.hasOwnProperty.call(
                        summary,
                        key
                    )
                ) {
                    element.textContent =
                        formatAttendanceCount(
                            summary[key]
                        );
                }
            });

        const confirmButton = document.getElementById(
            'attendanceConfirmButton'
        );

        if (confirmButton) {
            confirmButton.disabled = !canConfirm;
            confirmButton.title = canConfirm
                ? 'Confirm attendance import'
                : 'Resolve all review, error, and duplicate rows first.';
        }
    }

    function attendanceVisibleMetaFromDom() {
        const list = document.getElementById(
            'attendanceEmployeeGroupList'
        );

        if (!list) {
            return {
                employeeCount: 0,
                rowCount: 0,
            };
        }

        return {
            employeeCount: Number(
                list.dataset.attendanceGroupCount
                || list.querySelectorAll(
                    '.employee-attendance-group'
                ).length
            ),
            rowCount: Number(
                list.dataset.attendanceRowCount
                || list.querySelectorAll(
                    '.attendance-record-card'
                ).length
            ),
        };
    }

    function updateAttendanceVisibleMeta(meta = null) {
        const resolvedMeta = meta
            ? {
                employeeCount: Number(
                    meta.employee_count || 0
                ),
                rowCount: Number(
                    meta.row_count || 0
                ),
            }
            : attendanceVisibleMetaFromDom();

        const employeeCount = document.getElementById(
            'attendanceEmployeeCount'
        );
        const visibleRowCount = document.getElementById(
            'attendanceVisibleRowCount'
        );
        const toolbarCount = document.getElementById(
            'attendanceToolbarVisibleCount'
        );
        const toolbar = document.getElementById(
            'attendanceReviewToolbar'
        );

        if (employeeCount) {
            employeeCount.textContent = `${
                formatAttendanceCount(
                    resolvedMeta.employeeCount
                )
            } employees`;
        }

        if (visibleRowCount) {
            visibleRowCount.textContent = `${
                formatAttendanceCount(
                    resolvedMeta.rowCount
                )
            } attendance rows`;
        }

        if (toolbarCount) {
            toolbarCount.textContent = `${
                formatAttendanceCount(
                    resolvedMeta.rowCount
                )
            } visible records`;
        }

        toolbar?.classList.toggle(
            'd-none',
            resolvedMeta.rowCount === 0
        );
    }

    function setAttendanceRowHighlight(rowId) {
        if (!rowId) {
            return;
        }

        const row = document.getElementById(
            `attendance-record-${rowId}`
        );

        if (!row) {
            return;
        }

        row.classList.add(
            'is-recently-updated'
        );

        window.setTimeout(() => {
            row.classList.remove(
                'is-recently-updated'
            );
        }, 2400);
    }

    function restoreAttendanceAnchor(anchor = null) {
        if (!anchor) {
            return;
        }

        const row = anchor.rowId
            ? document.getElementById(
                `attendance-record-${anchor.rowId}`
            )
            : null;

        if (
            row
            && Number.isFinite(anchor.viewportTop)
        ) {
            const updatedTop =
                row.getBoundingClientRect().top;

            window.scrollBy({
                top: updatedTop - anchor.viewportTop,
                left: 0,
                behavior: 'auto',
            });

            return;
        }

        if (Number.isFinite(anchor.scrollY)) {
            const maxScroll = Math.max(
                document.documentElement.scrollHeight
                    - window.innerHeight,
                0
            );

            window.scrollTo({
                top: Math.min(
                    anchor.scrollY,
                    maxScroll
                ),
                left: 0,
                behavior: 'auto',
            });
        }
    }

    function parseAttendanceHtml(html) {
        const template = document.createElement(
            'template'
        );

        template.innerHTML = String(html || '').trim();

        return template.content;
    }

    function replaceAttendanceGroup(
        html,
        groupKey
    ) {
        const content = document.getElementById(
            'attendanceReviewContent'
        );

        if (!content || !groupKey) {
            return false;
        }

        const fragment = parseAttendanceHtml(html);
        const selector =
            `[data-attendance-group-key="${
                escapeAttendanceSelector(groupKey)
            }"]`;

        const currentGroup = content.querySelector(
            selector
        );
        const refreshedGroup = fragment.querySelector(
            selector
        );

        if (
            currentGroup
            && refreshedGroup
        ) {
            currentGroup.replaceWith(
                refreshedGroup.cloneNode(true)
            );

            return true;
        }

        if (
            currentGroup
            && !refreshedGroup
        ) {
            currentGroup.remove();

            if (
                !content.querySelector(
                    '.employee-attendance-group'
                )
            ) {
                content.replaceChildren(
                    fragment.cloneNode(true)
                );
            }

            return true;
        }

        return false;
    }

    async function loadAttendanceReviewData({
        historyMode = 'none',
        groupKeys = [],
        highlightRowId = null,
        anchor = null,
        loadingMessage = 'Loading attendance data...',
    } = {}) {
        const content = document.getElementById(
            'attendanceReviewContent'
        );

        if (!content || !attendanceReviewDataUrl) {
            return null;
        }

        attendanceDataAbortController?.abort();
        attendanceDataAbortController =
            new AbortController();

        const requestSequence =
            ++attendanceDataRequestSequence;

        setAttendanceDataLoading(
            true,
            loadingMessage
        );

        try {
            const response = await fetch(
                attendanceDataRequestUrl({
                    groupKeys,
                    highlightRowId,
                }),
                {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With':
                            'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    cache: 'no-store',
                    signal:
                        attendanceDataAbortController
                            .signal,
                }
            );

            const result = await parseAttendanceResponse(
                response
            );

            if (
                !response.ok
                || result.success === false
            ) {
                throw new Error(
                    result.message
                    || 'Attendance data could not be loaded.'
                );
            }

            if (
                requestSequence
                !== attendanceDataRequestSequence
            ) {
                return null;
            }

            if (groupKeys.length === 1) {
                const groupWasReplaced =
                    replaceAttendanceGroup(
                        result.html,
                        groupKeys[0]
                    );

                if (!groupWasReplaced) {
                    return await loadAttendanceReviewData({
                        historyMode,
                        highlightRowId,
                        anchor,
                        loadingMessage,
                    });
                }

                updateAttendanceVisibleMeta();
            } else {
                content.innerHTML = result.html || '';
                updateAttendanceVisibleMeta(
                    result.meta || null
                );
            }

            updateAttendanceSummary(
                result.summary || {},
                Boolean(result.can_confirm)
            );

            syncAttendanceBulkActionBar();
            updateAttendanceHistory(historyMode);
            restoreAttendanceAnchor(anchor);
            setAttendanceRowHighlight(
                highlightRowId
            );

            setAttendanceDataStatus(
                `Updated ${
                    new Date().toLocaleTimeString(
                        [],
                        {
                            hour: '2-digit',
                            minute: '2-digit',
                        }
                    )
                }`,
                false
            );

            return result;
        } catch (error) {
            if (error?.name === 'AbortError') {
                return null;
            }

            const message = error?.message
                || 'Attendance data could not be loaded.';

            showAttendanceToast(
                message,
                'danger'
            );

            setAttendanceDataStatus(
                'Load failed',
                false
            );

            throw error;
        } finally {
            if (
                requestSequence
                === attendanceDataRequestSequence
            ) {
                setAttendanceDataLoading(false);
            }
        }
    }

    function clearAttendanceEditErrors() {
        const form = document.getElementById(
            'editAttendanceForm'
        );
        const alert = document.getElementById(
            'editAttendanceFormAlert'
        );

        form?.querySelectorAll(
            '.is-invalid'
        ).forEach(field => {
            field.classList.remove(
                'is-invalid'
            );
        });

        if (alert) {
            alert.replaceChildren();
            alert.classList.add('d-none');
        }
    }

    function showFormErrors({
        form,
        alert,
        errors = {},
        fallbackMessage = null,
    }) {
        if (!alert) {
            return;
        }

        const messages = [];

        Object.entries(
            errors || {}
        ).forEach(([key, fieldMessages]) => {
            const normalizedFieldName = key
                .replace(/^resolution\./, '')
                .split('.')[0];

            const directField = form?.querySelector(
                `[name="${normalizedFieldName}"]`
            );

            const nestedField = document.querySelector(
                `[form="${form?.id}"][name="${
                    key.startsWith('resolution.')
                        ? `resolution[${normalizedFieldName}]`
                        : normalizedFieldName
                }"]`
            );

            (directField || nestedField)?.classList.add(
                'is-invalid'
            );

            (
                Array.isArray(fieldMessages)
                    ? fieldMessages
                    : [fieldMessages]
            )
                .filter(Boolean)
                .forEach(message => {
                    messages.push(String(message));
                });
        });

        if (
            messages.length === 0
            && fallbackMessage
        ) {
            messages.push(fallbackMessage);
        }

        if (messages.length === 0) {
            messages.push(
                'The requested changes could not be saved.'
            );
        }

        const list = document.createElement('ul');
        list.className = 'mb-0 ps-3';

        messages.forEach(message => {
            const item = document.createElement('li');
            item.textContent = message;
            list.appendChild(item);
        });

        alert.replaceChildren(list);
        alert.classList.remove('d-none');
        alert.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
        });
    }

    function showAttendanceEditErrors(
        errors,
        fallbackMessage = null
    ) {
        showFormErrors({
            form: document.getElementById(
                'editAttendanceForm'
            ),
            alert: document.getElementById(
                'editAttendanceFormAlert'
            ),
            errors,
            fallbackMessage,
        });
    }

    function clearAttendanceBulkErrors() {
        const form = document.getElementById(
            'bulkUpdateForm'
        );
        const alert = document.getElementById(
            'bulkUpdateFormAlert'
        );

        document
            .querySelectorAll(
                '[form="bulkUpdateForm"].is-invalid'
            )
            .forEach(field => {
                field.classList.remove(
                    'is-invalid'
                );
            });

        form?.querySelectorAll(
            '.is-invalid'
        ).forEach(field => {
            field.classList.remove(
                'is-invalid'
            );
        });

        if (alert) {
            alert.replaceChildren();
            alert.classList.add('d-none');
        }
    }

    function showAttendanceBulkErrors(
        errors,
        fallbackMessage = null
    ) {
        showFormErrors({
            form: document.getElementById(
                'bulkUpdateForm'
            ),
            alert: document.getElementById(
                'bulkUpdateFormAlert'
            ),
            errors,
            fallbackMessage,
        });
    }

    function setAttendanceSaveLoading(loading) {
        const button = document.getElementById(
            'saveAttendanceChangesButton'
        );

        if (!button) {
            return;
        }

        button.disabled = loading;

        button
            .querySelector('.default-text')
            ?.classList.toggle(
                'd-none',
                loading
            );

        button
            .querySelector('.loading-text')
            ?.classList.toggle(
                'd-none',
                !loading
            );
    }

    function setAttendanceBulkLoading(loading) {
        const button = document.getElementById(
            'bulkUpdateSubmitButton'
        );

        if (!button) {
            return;
        }

        button.disabled = loading;

        button
            .querySelector('.default-text')
            ?.classList.toggle(
                'd-none',
                loading
            );

        button
            .querySelector('.loading-text')
            ?.classList.toggle(
                'd-none',
                !loading
            );
    }

    function populateAttendanceEditForm(row) {
        const form = document.getElementById(
            'editAttendanceForm'
        );

        if (!form) {
            return false;
        }

        clearAttendanceEditErrors();

        form.action = row.update_url || '';
        form.dataset.rowId = String(row.id || '');
        form.dataset.groupKey =
            row.group_key || '';
        form.dataset.groupDomKey =
            row.group_dom_key || '';

        form.querySelectorAll(
            '[data-field]'
        ).forEach(field => {
            const key = field.dataset.field;
            const value = row[key];

            if (field.type === 'checkbox') {
                field.checked = Boolean(value);
                return;
            }

            if (
                field.type === 'time'
                && value
            ) {
                field.value = String(value).slice(
                    0,
                    5
                );
                return;
            }

            field.value = value ?? '';
        });

        return true;
    }

    function openAttendanceReviewModal(row) {
        if (
            !row
            || typeof row !== 'object'
        ) {
            return;
        }

        if (!populateAttendanceEditForm(row)) {
            return;
        }

        getAttendanceEditModal()?.show();
    }

    function openAttendanceReviewModalFromElement(
        element
    ) {
        const row = decodeAttendancePayload(
            element.dataset.attendancePayload
        );

        if (row) {
            openAttendanceReviewModal(row);
        }
    }

    function syncAttendanceBulkActionBar() {
        const selectAllRows = document.getElementById(
            'selectAllRows'
        );
        const rowCheckboxes = Array.from(
            document.querySelectorAll(
                '.row-checkbox'
            )
        );
        const selected = rowCheckboxes.filter(
            checkbox => checkbox.checked
        );
        const bulkActionBar = document.getElementById(
            'bulkActionBar'
        );
        const selectedRowCount = document.getElementById(
            'selectedRowCount'
        );

        if (selectedRowCount) {
            selectedRowCount.textContent =
                formatAttendanceCount(
                    selected.length
                );
        }

        bulkActionBar?.classList.toggle(
            'd-none',
            selected.length === 0
        );

        if (selectAllRows) {
            selectAllRows.checked =
                selected.length > 0
                && selected.length
                    === rowCheckboxes.length;

            selectAllRows.indeterminate =
                selected.length > 0
                && selected.length
                    < rowCheckboxes.length;
        }
    }

    function initializeAttendanceBulkSelection() {
        document.addEventListener(
            'change',
            event => {
                if (
                    event.target?.id
                    === 'selectAllRows'
                ) {
                    document
                        .querySelectorAll(
                            '.row-checkbox'
                        )
                        .forEach(checkbox => {
                            checkbox.checked =
                                event.target.checked;
                        });

                    syncAttendanceBulkActionBar();
                    return;
                }

                if (
                    event.target?.classList?.contains(
                        'row-checkbox'
                    )
                ) {
                    syncAttendanceBulkActionBar();
                }
            }
        );
    }

    function initializeAttendanceRowEditors() {
        document.addEventListener(
            'click',
            event => {
                const editButton =
                    event.target.closest(
                        '.edit-row-button[data-attendance-payload]'
                    );

                if (editButton) {
                    event.preventDefault();
                    event.stopPropagation();

                    openAttendanceReviewModalFromElement(
                        editButton
                    );

                    return;
                }

                const card = event.target.closest(
                    '.needs-review-row[data-attendance-payload]'
                );

                if (!card) {
                    return;
                }

                if (
                    event.target.closest(
                        'input, button, a, select, textarea, label, .dropdown'
                    )
                ) {
                    return;
                }

                openAttendanceReviewModalFromElement(
                    card
                );
            }
        );

        document.addEventListener(
            'keydown',
            event => {
                if (
                    !['Enter', ' '].includes(
                        event.key
                    )
                ) {
                    return;
                }

                const card = event.target.closest(
                    '.needs-review-row[data-attendance-payload]'
                );

                if (!card) {
                    return;
                }

                event.preventDefault();

                openAttendanceReviewModalFromElement(
                    card
                );
            }
        );
    }

    function initializeAttendanceFilters() {
        const form = getAttendanceFilterForm();
        const resetButton = document.getElementById(
            'resetReviewFilters'
        );
        const searchInput = document.getElementById(
            'search'
        );

        form?.addEventListener(
            'submit',
            event => {
                event.preventDefault();

                loadAttendanceReviewData({
                    historyMode: 'push',
                    loadingMessage:
                        'Applying attendance filters...',
                }).catch(() => {});
            }
        );

        document
            .querySelectorAll(
                '.filter-auto-submit'
            )
            .forEach(field => {
                field.addEventListener(
                    'change',
                    () => {
                        loadAttendanceReviewData({
                            historyMode: 'push',
                            loadingMessage:
                                'Applying attendance filters...',
                        }).catch(() => {});
                    }
                );
            });

        searchInput?.addEventListener(
            'input',
            () => {
                window.clearTimeout(
                    attendanceSearchTimer
                );

                attendanceSearchTimer =
                    window.setTimeout(() => {
                        loadAttendanceReviewData({
                            historyMode: 'replace',
                            loadingMessage:
                                'Searching attendance...',
                        }).catch(() => {});
                    }, 550);
            }
        );

        resetButton?.addEventListener(
            'click',
            event => {
                event.preventDefault();
                window.clearTimeout(
                    attendanceSearchTimer
                );

                clearAttendanceFilters();

                loadAttendanceReviewData({
                    historyMode: 'push',
                    loadingMessage:
                        'Resetting attendance filters...',
                }).catch(() => {});
            }
        );

        window.addEventListener(
            'popstate',
            () => {
                syncAttendanceFilterFormFromUrl();

                loadAttendanceReviewData({
                    historyMode: 'none',
                    loadingMessage:
                        'Restoring attendance filters...',
                }).catch(() => {});
            }
        );
    }

    function initializeAttendanceAsyncEdit() {
        const form = document.getElementById(
            'editAttendanceForm'
        );
        const modalElement = document.getElementById(
            'editAttendanceModal'
        );

        form?.addEventListener(
            'submit',
            async event => {
                event.preventDefault();
                clearAttendanceEditErrors();

                const rowId = form.dataset.rowId;
                const originalGroupKey =
                    form.dataset.groupKey;

                if (!form.action || !rowId) {
                    showAttendanceEditErrors(
                        null,
                        'Attendance row to update was not found.'
                    );

                    return;
                }

                const currentCard =
                    document.getElementById(
                        `attendance-record-${rowId}`
                    );

                const anchor = {
                    rowId,
                    viewportTop:
                        currentCard
                            ?.getBoundingClientRect()
                            .top,
                    scrollY: window.scrollY,
                };

                setAttendanceSaveLoading(true);

                try {
                    const response = await fetch(
                        form.action,
                        {
                            method: 'POST',
                            headers: {
                                'Accept':
                                    'application/json',
                                'X-Requested-With':
                                    'XMLHttpRequest',
                            },
                            body: new FormData(form),
                            credentials:
                                'same-origin',
                        }
                    );

                    const result =
                        await parseAttendanceResponse(
                            response
                        );

                    if (response.status === 422) {
                        showAttendanceEditErrors(
                            result.errors || {},
                            result.message
                        );

                        return;
                    }

                    if (
                        !response.ok
                        || result.success === false
                    ) {
                        throw new Error(
                            result.message
                            || 'Attendance row could not be updated.'
                        );
                    }

                    const responseData =
                        result.data || {};

                    updateAttendanceSummary(
                        responseData.summary || {},
                        Boolean(
                            responseData.can_confirm
                        )
                    );

                    getAttendanceEditModal()?.hide();

                    const refreshGroupKeys = Array.from(
                        new Set(
                            responseData
                                .refresh_group_keys
                                || [
                                    originalGroupKey,
                                ].filter(Boolean)
                        )
                    );

                    const canRefreshOneGroup =
                        refreshGroupKeys.length === 1
                        && (
                            !responseData.previous_group_key
                            || responseData.previous_group_key
                                === responseData.group_key
                        );

                    await loadAttendanceReviewData({
                        groupKeys:
                            canRefreshOneGroup
                                ? refreshGroupKeys
                                : [],
                        highlightRowId: rowId,
                        anchor,
                        loadingMessage:
                            'Refreshing updated attendance...',
                    });

                    showAttendanceToast(
                        result.message
                        || 'Attendance row was updated.',
                        'success'
                    );
                } catch (error) {
                    const message = error?.message
                        || 'Attendance row could not be updated.';

                    showAttendanceEditErrors(
                        null,
                        message
                    );

                    showAttendanceToast(
                        message,
                        'danger'
                    );
                } finally {
                    setAttendanceSaveLoading(false);
                }
            }
        );

        modalElement?.addEventListener(
            'hidden.bs.modal',
            () => {
                clearAttendanceEditErrors();
                setAttendanceSaveLoading(false);
            }
        );
    }

    function initializeAttendanceAsyncBulkUpdate() {
        const form = document.getElementById(
            'bulkUpdateForm'
        );
        const modalElement = document.getElementById(
            'bulkUpdateModal'
        );

        form?.addEventListener(
            'submit',
            async event => {
                event.preventDefault();
                clearAttendanceBulkErrors();

                const selectedRows =
                    document.querySelectorAll(
                        '.row-checkbox:checked'
                    );

                if (selectedRows.length === 0) {
                    showAttendanceBulkErrors(
                        null,
                        'Select at least one attendance row.'
                    );

                    getAttendanceBulkModal()?.show();
                    return;
                }

                setAttendanceBulkLoading(true);

                const anchor = {
                    scrollY: window.scrollY,
                };

                try {
                    const response = await fetch(
                        form.action,
                        {
                            method: 'POST',
                            headers: {
                                'Accept':
                                    'application/json',
                                'X-Requested-With':
                                    'XMLHttpRequest',
                            },
                            body: new FormData(form),
                            credentials:
                                'same-origin',
                        }
                    );

                    const result =
                        await parseAttendanceResponse(
                            response
                        );

                    if (response.status === 422) {
                        showAttendanceBulkErrors(
                            result.errors || {},
                            result.message
                        );

                        getAttendanceBulkModal()?.show();
                        return;
                    }

                    if (
                        !response.ok
                        || result.success === false
                    ) {
                        throw new Error(
                            result.message
                            || 'Bulk attendance update failed.'
                        );
                    }

                    const responseData =
                        result.data || {};

                    updateAttendanceSummary(
                        responseData.summary || {},
                        Boolean(
                            responseData.can_confirm
                        )
                    );

                    getAttendanceBulkModal()?.hide();
                    form.reset();
                    syncAttendanceBulkActionBar();

                    await loadAttendanceReviewData({
                        anchor,
                        loadingMessage:
                            'Refreshing attendance data...',
                    });

                    showAttendanceToast(
                        result.message
                        || 'Selected attendance rows were updated.',
                        'success'
                    );
                } catch (error) {
                    const message = error?.message
                        || 'Bulk attendance update failed.';

                    showAttendanceBulkErrors(
                        null,
                        message
                    );

                    getAttendanceBulkModal()?.show();

                    showAttendanceToast(
                        message,
                        'danger'
                    );
                } finally {
                    setAttendanceBulkLoading(false);
                }
            }
        );

        modalElement?.addEventListener(
            'hidden.bs.modal',
            () => {
                clearAttendanceBulkErrors();
                setAttendanceBulkLoading(false);
            }
        );
    }

    function showAttendanceToast(
        message,
        type = 'success'
    ) {
        const container = document.getElementById(
            'attendanceAsyncToastContainer'
        );

        if (
            !container
            || !window.bootstrap?.Toast
        ) {
            return;
        }

        const backgroundClass = {
            success: 'text-bg-success',
            danger: 'text-bg-danger',
            warning: 'text-bg-warning',
            info: 'text-bg-info',
        }[type] || 'text-bg-success';

        const toastElement =
            document.createElement('div');

        toastElement.className =
            `toast align-items-center ${
                backgroundClass
            } border-0`;

        toastElement.setAttribute(
            'role',
            'status'
        );
        toastElement.setAttribute(
            'aria-live',
            'polite'
        );
        toastElement.setAttribute(
            'aria-atomic',
            'true'
        );

        const wrapper =
            document.createElement('div');
        wrapper.className = 'd-flex';

        const body =
            document.createElement('div');
        body.className =
            'toast-body fw-semibold';
        body.textContent = message;

        const close =
            document.createElement('button');
        close.type = 'button';
        close.className =
            'btn-close btn-close-white me-2 m-auto';
        close.setAttribute(
            'data-bs-dismiss',
            'toast'
        );
        close.setAttribute(
            'aria-label',
            'Close'
        );

        wrapper.append(body, close);
        toastElement.appendChild(wrapper);
        container.appendChild(toastElement);

        const toast =
            new window.bootstrap.Toast(
                toastElement,
                {
                    delay: 3400,
                }
            );

        toast.show();

        toastElement.addEventListener(
            'hidden.bs.toast',
            () => toastElement.remove()
        );
    }

    function initializeAttendanceConfirmationDialog() {
        const modalElement = document.getElementById(
            'actionConfirmationModal'
        );
        const confirmationTitle = document.getElementById(
            'confirmationTitle'
        );
        const confirmationMessage = document.getElementById(
            'confirmationMessage'
        );
        const confirmationIcon = document.getElementById(
            'confirmationIcon'
        );
        const confirmationIconGlyph =
            document.getElementById(
                'confirmationIconGlyph'
            );
        const confirmationSubmitButton =
            document.getElementById(
                'confirmationSubmitButton'
            );

        document.addEventListener(
            'click',
            event => {
                const trigger = event.target.closest(
                    '[data-confirm-form]'
                );

                if (
                    !trigger
                    || trigger.disabled
                ) {
                    return;
                }

                pendingAttendanceConfirmationForm =
                    document.getElementById(
                        trigger.dataset.confirmForm
                    );

                const variant =
                    trigger.dataset.confirmVariant
                    || 'primary';

                if (confirmationTitle) {
                    confirmationTitle.textContent =
                        trigger.dataset.confirmTitle
                        || 'Confirm Action';
                }

                if (confirmationMessage) {
                    confirmationMessage.textContent =
                        trigger.dataset.confirmMessage
                        || 'Are you sure you want to continue?';
                }

                if (confirmationSubmitButton) {
                    confirmationSubmitButton.textContent =
                        trigger.dataset.confirmLabel
                        || 'Confirm';

                    confirmationSubmitButton.className =
                        `btn btn-modern ${
                            variant === 'danger'
                                ? 'btn-danger'
                                : 'btn-primary'
                        }`;
                }

                if (confirmationIcon) {
                    confirmationIcon.className =
                        `confirmation-icon ${
                            variant === 'danger'
                                ? 'is-danger'
                                : 'is-primary'
                        }`;
                }

                if (confirmationIconGlyph) {
                    confirmationIconGlyph.className =
                        variant === 'danger'
                            ? 'bi bi-exclamation-triangle-fill'
                            : 'bi bi-check-lg';
                }

                getAttendanceConfirmationModal()?.show();
            }
        );

        confirmationSubmitButton?.addEventListener(
            'click',
            () => {
                if (!pendingAttendanceConfirmationForm) {
                    return;
                }

                confirmationSubmitButton.disabled = true;
                confirmationSubmitButton.innerHTML = `
                    <span
                        class="spinner-border spinner-border-sm me-2"
                        aria-hidden="true"
                    ></span>
                    Processing...
                `;

                pendingAttendanceConfirmationForm.submit();
            }
        );

        modalElement?.addEventListener(
            'hidden.bs.modal',
            () => {
                pendingAttendanceConfirmationForm = null;

                if (confirmationSubmitButton) {
                    confirmationSubmitButton.disabled = false;
                    confirmationSubmitButton.textContent =
                        'Confirm';
                    confirmationSubmitButton.className =
                        'btn btn-primary btn-modern';
                }
            }
        );
    }

    document.addEventListener(
        'DOMContentLoaded',
        () => {
            initializeAttendanceBulkSelection();
            initializeAttendanceRowEditors();
            initializeAttendanceFilters();
            initializeAttendanceAsyncEdit();
            initializeAttendanceAsyncBulkUpdate();
            initializeAttendanceConfirmationDialog();

            loadAttendanceReviewData({
                historyMode: 'none',
                loadingMessage:
                    'Loading latest attendance data...',
            }).catch(() => {});
        }
    );
</script>
@endpush
