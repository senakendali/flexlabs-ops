@php
    $student = $reportCard->student;

    $studentName = $studentName
        ?? $student->name
        ?? $student->full_name
        ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

    if (!$studentName) {
        $studentName = $student->email ?? 'Student #' . $reportCard->student_id;
    }

    $scoreSnapshot = $reportCard->score_snapshot ?? [];

    if (is_string($scoreSnapshot)) {
        $scoreSnapshot = json_decode($scoreSnapshot, true) ?: [];
    }

    $components = $scoreSnapshot['components'] ?? [];

    $ruleSnapshot = $reportCard->rule_snapshot ?? [];

    if (is_string($ruleSnapshot)) {
        $ruleSnapshot = json_decode($ruleSnapshot, true) ?: [];
    }

    $statusLabel = ucwords(str_replace('_', ' ', $reportCard->status));

    $logoPath = public_path('images/logo-black.png');

    $resultPassed = in_array($reportCard->status, ['passed', 'published'], true);

    $issuedDate = optional($reportCard->published_at)->format('d M Y')
        ?? optional($reportCard->generated_at)->format('d M Y')
        ?? now()->format('d M Y');

    $rules = [
        [
            'label' => 'Passing Score',
            'required' => number_format((float)($ruleSnapshot['passing_score'] ?? 0), 0),
            'actual' => number_format((float) $reportCard->final_score, 2),
            'passed' => (bool)($ruleSnapshot['passes_score'] ?? false),
        ],
        [
            'label' => 'Minimum Attendance',
            'required' => number_format((float)($ruleSnapshot['min_attendance_percent'] ?? 0), 0) . '%',
            'actual' => number_format((float) $reportCard->attendance_percent, 0) . '%',
            'passed' => (bool)($ruleSnapshot['passes_attendance'] ?? false),
        ],
        [
            'label' => 'Minimum Progress',
            'required' => number_format((float)($ruleSnapshot['min_progress_percent'] ?? 0), 0) . '%',
            'actual' => number_format((float) $reportCard->progress_percent, 0) . '%',
            'passed' => (bool)($ruleSnapshot['passes_progress'] ?? false),
        ],
        [
            'label' => 'Final Project',
            'required' => !empty($ruleSnapshot['requires_final_project']) ? 'Required' : 'Not Required',
            'actual' => !empty($ruleSnapshot['passes_final_project']) ? 'Completed' : 'Not Completed',
            'passed' => (bool)($ruleSnapshot['passes_final_project'] ?? false),
        ],
        [
            'label' => 'Required Scores',
            'required' => 'Complete',
            'actual' => !empty($ruleSnapshot['has_missing_required_scores']) ? 'Incomplete' : 'Complete',
            'passed' => empty($ruleSnapshot['has_missing_required_scores']),
        ],
    ];
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report Card - {{ $studentName }}</title>

    <style>
        @page {
            margin: 32px 36px 38px 36px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
            color: #1f2937;
            line-height: 1.45;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-muted {
            color: #6b7280;
        }

        .text-primary {
            color: #5B3E8E;
        }

        .fw-bold {
            font-weight: bold;
        }

        .header-table {
            margin-bottom: 18px;
        }

        .header-table td {
            vertical-align: top;
        }

        .brand-logo {
            width: 150px;
            height: auto;
        }

        .brand-fallback {
            font-size: 20px;
            font-weight: bold;
            color: #5B3E8E;
        }

        .document-title {
            text-align: right;
        }

        .document-title h1 {
            margin: 0;
            padding: 0;
            font-size: 24px;
            line-height: 1.05;
            letter-spacing: 0.8px;
            color: #111827;
            font-weight: bold;
        }

        .document-subtitle {
            margin-top: 6px;
            font-size: 10px;
            color: #6b7280;
        }

        .accent-line {
            height: 4px;
            background: #5B3E8E;
            margin-bottom: 16px;
        }

        .official-box {
            border: 1px solid #d9d4e6;
            background: #fbfafc;
            padding: 12px 14px;
            margin-bottom: 14px;
        }

        .official-title {
            font-size: 13px;
            font-weight: bold;
            color: #5B3E8E;
            margin-bottom: 5px;
        }

        .official-text {
            font-size: 10px;
            color: #374151;
        }

        .section {
            margin-top: 14px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 7px;
            text-transform: uppercase;
            letter-spacing: 0.35px;
        }

        .info-table td {
            border: 1px solid #e5e7eb;
            padding: 7px 9px;
            vertical-align: top;
        }

        .info-label {
            display: block;
            font-size: 8.5px;
            color: #6b7280;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.25px;
        }

        .info-value {
            font-size: 10.5px;
            font-weight: bold;
            color: #111827;
        }

        .summary-table td {
            border: 1px solid #d9d4e6;
            padding: 9px 8px;
            vertical-align: middle;
            text-align: center;
        }

        .summary-label {
            font-size: 8.5px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.25px;
            margin-bottom: 3px;
        }

        .summary-value {
            font-size: 18px;
            color: #111827;
            font-weight: bold;
        }

        .summary-value.primary {
            color: #5B3E8E;
        }

        .result-status {
            display: inline-block;
            padding: 4px 9px;
            font-size: 9px;
            font-weight: bold;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #374151;
        }

        .result-status.passed {
            border-color: #b7dfc5;
            background: #eefaf2;
            color: #237a3b;
        }

        .result-status.failed {
            border-color: #f2bec5;
            background: #fff1f2;
            color: #b42335;
        }

        .data-table {
            border: 1px solid #e5e7eb;
        }

        .data-table th {
            border: 1px solid #ded7ed;
            background: #f3f0f8;
            color: #3f2d64;
            padding: 7px 6px;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.25px;
            font-weight: bold;
        }

        .data-table td {
            border: 1px solid #e5e7eb;
            padding: 7px 6px;
            vertical-align: top;
        }

        .data-table tfoot td,
        .data-table tfoot th {
            background: #fbfafc;
            border: 1px solid #ded7ed;
            padding: 8px 6px;
            font-weight: bold;
        }

        .component-name {
            font-weight: bold;
            color: #111827;
        }

        .component-desc {
            margin-top: 2px;
            font-size: 8.5px;
            color: #6b7280;
        }

        .status-pill {
            display: inline-block;
            padding: 3px 8px;
            font-size: 8.5px;
            font-weight: bold;
            border: 1px solid #d1d5db;
            color: #374151;
            background: #ffffff;
        }

        .status-pass {
            color: #237a3b;
            background: #eefaf2;
            border-color: #b7dfc5;
        }

        .status-fail {
            color: #b42335;
            background: #fff1f2;
            border-color: #f2bec5;
        }

        .rubric-block {
            margin-top: 6px;
            padding: 6px 7px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
        }

        .rubric-title {
            font-size: 8.5px;
            font-weight: bold;
            color: #5B3E8E;
            text-transform: uppercase;
            letter-spacing: 0.25px;
            margin-bottom: 4px;
        }

        .rubric-table td {
            border: 0;
            padding: 2px 0;
            font-size: 8.5px;
            background: transparent;
        }

        .note-table td {
            border: 1px solid #e5e7eb;
            padding: 8px 9px;
            vertical-align: top;
        }

        .note-title {
            font-size: 8.5px;
            font-weight: bold;
            color: #5B3E8E;
            text-transform: uppercase;
            letter-spacing: 0.25px;
            margin-bottom: 4px;
        }

        .note-content {
            font-size: 10px;
            color: #374151;
        }

        .signature-table {
            margin-top: 34px;
            page-break-inside: avoid;
        }

        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding-top: 6px;
        }

        .signature-role {
            font-size: 9px;
            color: #6b7280;
            margin-bottom: 42px;
        }

        .signature-line {
            border-top: 1px solid #111827;
            width: 180px;
            margin: 0 auto 5px auto;
        }

        .signature-name {
            font-size: 10px;
            font-weight: bold;
            color: #111827;
        }

        .small-note {
            font-size: 8.5px;
            color: #6b7280;
            margin-top: 4px;
        }
    </style>
</head>

<body>

<table class="header-table">
    <tr>
        <td style="width: 45%;">
            @if(file_exists($logoPath))
                <img src="{{ $logoPath }}" class="brand-logo" alt="FlexLabs">
            @else
                <span class="brand-fallback">FlexLabs</span>
            @endif
        </td>
        <td class="document-title">
            <h1>REPORT CARD</h1>
            <div class="document-subtitle">
                Report No: {{ $reportCard->report_no }}<br>
                Issue Date: {{ $issuedDate }}
            </div>
        </td>
    </tr>
</table>

<div class="accent-line"></div>

<div class="official-box">
    <div class="official-title">Academic Performance Report</div>
    <div class="official-text">
        This report summarizes the student's academic performance based on attendance, learning progress,
        assessment components, and program completion requirements.
    </div>
</div>

<div class="section">
    <div class="section-title">Student Information</div>

    <table class="info-table">
        <tr>
            <td style="width: 50%;">
                <span class="info-label">Student Name</span>
                <span class="info-value">{{ $studentName }}</span>
            </td>
            <td style="width: 50%;">
                <span class="info-label">Email</span>
                <span class="info-value">{{ $student->email ?? '-' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="info-label">Program</span>
                <span class="info-value">{{ $reportCard->program->name ?? '-' }}</span>
            </td>
            <td>
                <span class="info-label">Batch</span>
                <span class="info-value">{{ $reportCard->batch->name ?? ('Batch #' . $reportCard->batch_id) }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="info-label">Assessment Template</span>
                <span class="info-value">{{ $reportCard->template->name ?? '-' }}</span>
            </td>
            <td>
                <span class="info-label">Report Status</span>
                <span class="info-value">{{ $statusLabel }}</span>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Result Summary</div>

    <table class="summary-table">
        <tr>
            <td style="width: 20%;">
                <div class="summary-label">Final Score</div>
                <div class="summary-value primary">{{ number_format((float) $reportCard->final_score, 2) }}</div>
            </td>
            <td style="width: 20%;">
                <div class="summary-label">Grade</div>
                <div class="summary-value">{{ $reportCard->grade ?? '-' }}</div>
            </td>
            <td style="width: 20%;">
                <div class="summary-label">Attendance</div>
                <div class="summary-value">{{ number_format((float) $reportCard->attendance_percent, 0) }}%</div>
            </td>
            <td style="width: 20%;">
                <div class="summary-label">Progress</div>
                <div class="summary-value">{{ number_format((float) $reportCard->progress_percent, 0) }}%</div>
            </td>
            <td style="width: 20%;">
                <div class="summary-label">Result</div>
                <div>
                    <span class="result-status {{ $resultPassed ? 'passed' : 'failed' }}">
                        {{ $resultPassed ? 'Passed' : 'Not Passed' }}
                    </span>
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Completion Requirements</div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 36%; text-align: left;">Requirement</th>
                <th style="width: 24%;">Required</th>
                <th style="width: 24%;">Actual</th>
                <th style="width: 16%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rules as $rule)
                <tr>
                    <td>{{ $rule['label'] }}</td>
                    <td class="text-center">{{ $rule['required'] }}</td>
                    <td class="text-center">{{ $rule['actual'] }}</td>
                    <td class="text-center">
                        @if($rule['passed'])
                            <span class="status-pill status-pass">Passed</span>
                        @else
                            <span class="status-pill status-fail">Not Passed</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(!empty($ruleSnapshot['missing_required_components']))
        <div class="small-note">
            <strong>Missing required components:</strong>
            @foreach($ruleSnapshot['missing_required_components'] as $missing)
                {{ $missing['name'] ?? '-' }}{{ !$loop->last ? ', ' : '' }}
            @endforeach
        </div>
    @endif
</div>

<div class="section">
    <div class="section-title">Assessment Score Breakdown</div>

    @if(count($components))
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 34%; text-align: left;">Component</th>
                    <th style="width: 14%;">Type</th>
                    <th style="width: 14%;">Raw Score</th>
                    <th style="width: 12%;">Weight</th>
                    <th style="width: 14%;">Weighted</th>
                    <th style="width: 12%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($components as $component)
                    <tr>
                        <td>
                            <div class="component-name">{{ $component['name'] ?? '-' }}</div>

                            @if(!empty($component['description']))
                                <div class="component-desc">{{ $component['description'] }}</div>
                            @endif

                            @if(!empty($component['rubric_scores']))
                                <div class="rubric-block">
                                    <div class="rubric-title">Rubric Detail</div>

                                    <table class="rubric-table">
                                        @foreach($component['rubric_scores'] as $rubricScore)
                                            <tr>
                                                <td>
                                                    <strong>{{ $rubricScore['criteria_name'] ?? '-' }}</strong>
                                                    @if(!empty($rubricScore['note']))
                                                        <div class="text-muted">{{ $rubricScore['note'] }}</div>
                                                    @endif
                                                </td>
                                                <td style="width: 75px;" class="text-right">
                                                    {{ number_format((float)($rubricScore['raw_score'] ?? 0), 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            @endif
                        </td>
                        <td class="text-center">
                            {{ ucwords(str_replace('_', ' ', $component['type'] ?? '-')) }}
                        </td>
                        <td class="text-right">
                            {{ number_format((float)($component['raw_score'] ?? 0), 2) }}
                        </td>
                        <td class="text-right">
                            {{ number_format((float)($component['weight'] ?? 0), 2) }}%
                        </td>
                        <td class="text-right">
                            {{ number_format((float)($component['weighted_score'] ?? 0), 2) }}
                        </td>
                        <td class="text-center">
                            @if(!empty($component['has_score']))
                                <span class="status-pill status-pass">Filled</span>
                            @else
                                <span class="status-pill status-fail">Missing</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right">Final Score</td>
                    <td class="text-right">{{ number_format((float) $reportCard->final_score, 2) }}</td>
                    <td class="text-center">Grade {{ $reportCard->grade ?? '-' }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <table class="info-table">
            <tr>
                <td>
                    <span class="text-muted">Score snapshot is empty.</span>
                </td>
            </tr>
        </table>
    @endif
</div>

<div class="section">
    <div class="section-title">Academic Notes</div>

    <table class="note-table">
        <tr>
            <td style="width: 50%;">
                <div class="note-title">Summary</div>
                <div class="note-content">{{ $reportCard->summary ?: 'No summary provided.' }}</div>
            </td>
            <td style="width: 50%;">
                <div class="note-title">Strengths</div>
                <div class="note-content">{{ $reportCard->strengths ?: 'No strengths note provided.' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="note-title">Improvements</div>
                <div class="note-content">{{ $reportCard->improvements ?: 'No improvement note provided.' }}</div>
            </td>
            <td>
                <div class="note-title">Instructor Note</div>
                <div class="note-content">{{ $reportCard->instructor_note ?: 'No instructor note provided.' }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="note-title">Academic Note</div>
                <div class="note-content">{{ $reportCard->academic_note ?: 'No academic note provided.' }}</div>
            </td>
        </tr>
    </table>
</div>

<table class="signature-table">
    <tr>
        <td>
            <div class="signature-role">Academic Representative</div>
            <div class="signature-line"></div>
            <div class="signature-name">FlexLabs Academic Team</div>
        </td>
        <td>
            <div class="signature-role">Student</div>
            <div class="signature-line"></div>
            <div class="signature-name">{{ $studentName }}</div>
        </td>
    </tr>
</table>

</body>
</html>