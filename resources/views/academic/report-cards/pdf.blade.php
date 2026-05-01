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

    $rules = [
        [
            'label' => 'Passing Score',
            'value' => number_format((float)($ruleSnapshot['passing_score'] ?? 0), 0),
            'passed' => (bool)($ruleSnapshot['passes_score'] ?? false),
        ],
        [
            'label' => 'Minimum Attendance',
            'value' => number_format((float)($ruleSnapshot['min_attendance_percent'] ?? 0), 0) . '%',
            'passed' => (bool)($ruleSnapshot['passes_attendance'] ?? false),
        ],
        [
            'label' => 'Minimum Progress',
            'value' => number_format((float)($ruleSnapshot['min_progress_percent'] ?? 0), 0) . '%',
            'passed' => (bool)($ruleSnapshot['passes_progress'] ?? false),
        ],
        [
            'label' => 'Final Project',
            'value' => !empty($ruleSnapshot['requires_final_project']) ? 'Required' : 'Not Required',
            'passed' => (bool)($ruleSnapshot['passes_final_project'] ?? false),
        ],
        [
            'label' => 'Required Scores',
            'value' => !empty($ruleSnapshot['has_missing_required_scores']) ? 'Missing' : 'Complete',
            'passed' => empty($ruleSnapshot['has_missing_required_scores']),
        ],
    ];

    $resultPassed = in_array($reportCard->status, ['passed', 'published'], true);
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report Card - {{ $studentName }}</title>

    <style>
        @page {
            margin: 30px 34px 34px 34px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #211936;
            line-height: 1.45;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .header-table td {
            vertical-align: top;
        }

        .brand-logo {
            width: 158px;
            height: auto;
        }

        .brand-fallback {
            font-size: 20px;
            font-weight: bold;
            color: #5B3E8E;
        }

        .doc-title {
            text-align: right;
        }

        .doc-title h1 {
            margin: 0;
            font-size: 24px;
            line-height: 1.05;
            color: #5B3E8E;
            letter-spacing: 0.45px;
        }

        .doc-title .meta {
            margin-top: 7px;
            color: #7a718d;
            font-size: 10px;
        }

        .hero {
            background: #5B3E8E;
            color: #ffffff;
            border-radius: 16px;
            padding: 19px 20px;
            margin-bottom: 14px;
        }

        .hero-table {
            width: 100%;
            border-collapse: collapse;
        }

        .hero-table td {
            vertical-align: top;
        }

        .student-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .student-meta {
            font-size: 10px;
            color: #eee8ff;
            line-height: 1.65;
        }

        .final-score-box {
            text-align: right;
            width: 190px;
        }

        .final-score-label {
            font-size: 9px;
            color: #eee8ff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .final-score {
            font-size: 34px;
            font-weight: bold;
            line-height: 1.05;
            margin-top: 3px;
        }

        .grade {
            display: inline-block;
            margin-top: 7px;
            padding: 4px 12px;
            border-radius: 20px;
            background: #FFBE04;
            color: #211936;
            font-size: 12px;
            font-weight: bold;
        }

        .result-badge {
            display: inline-block;
            margin-top: 7px;
            margin-left: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            background: #E5F6EA;
            color: #2f7d45;
            font-size: 9px;
            font-weight: bold;
        }

        .result-badge.failed {
            background: #FCE8EA;
            color: #b42335;
        }

        .section {
            margin-top: 13px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #5B3E8E;
            margin-bottom: 8px;
        }

        .section-caption {
            color: #7a718d;
            font-size: 9px;
            margin-top: -4px;
            margin-bottom: 8px;
        }

        .soft-card {
            border: 1px solid #ece8f3;
            border-radius: 14px;
            padding: 10px;
            background: #ffffff;
        }

        .info-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .info-grid td {
            width: 50%;
            vertical-align: top;
            padding: 8px 10px;
            border: 1px solid #ece8f3;
        }

        .info-grid tr:first-child td:first-child {
            border-top-left-radius: 12px;
        }

        .info-grid tr:first-child td:last-child {
            border-top-right-radius: 12px;
        }

        .info-grid tr:last-child td:first-child {
            border-bottom-left-radius: 12px;
        }

        .info-grid tr:last-child td:last-child {
            border-bottom-right-radius: 12px;
        }

        .info-label {
            color: #7a718d;
            font-size: 9px;
            margin-bottom: 3px;
        }

        .info-value {
            font-weight: bold;
            color: #211936;
        }

        .summary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-left: -8px;
            margin-right: -8px;
        }

        .summary-table td {
            width: 25%;
            padding: 11px 10px;
            border: 1px solid #ece8f3;
            border-radius: 14px;
            text-align: center;
            vertical-align: middle;
            background: #fbf8ff;
        }

        .summary-label {
            font-size: 9px;
            color: #7a718d;
        }

        .summary-value {
            margin-top: 4px;
            font-size: 17px;
            font-weight: bold;
            color: #211936;
        }

        .rules-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 6px;
        }

        .rules-table td {
            padding: 8px 10px;
            background: #fbf8ff;
            border-top: 1px solid #ece8f3;
            border-bottom: 1px solid #ece8f3;
        }

        .rules-table td:first-child {
            border-left: 1px solid #ece8f3;
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .rules-table td:last-child {
            border-right: 1px solid #ece8f3;
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .score-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            border: 1px solid #e6dff0;
        }

        .score-table th {
            background: #5B3E8E;
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid #5B3E8E;
            padding: 8px 6px;
        }

        .score-table td {
            border: 1px solid #ece8f3;
            padding: 8px 6px;
            vertical-align: top;
        }

        .score-table tbody tr:nth-child(even) td {
            background: #fbf8ff;
        }

        .score-table tfoot th,
        .score-table tfoot td {
            background: #f4effb;
            color: #211936;
            font-weight: bold;
            border: 1px solid #e1d8ee;
            padding: 9px 6px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .muted {
            color: #7a718d;
            font-size: 9px;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 14px;
            font-size: 9px;
            font-weight: bold;
        }

        .badge-pass {
            background: #E5F6EA;
            color: #2f7d45;
        }

        .badge-fail {
            background: #FCE8EA;
            color: #b42335;
        }

        .badge-neutral {
            background: #EEE9F7;
            color: #5B3E8E;
        }

        .badge-warning {
            background: #FFF3CD;
            color: #8a6400;
        }

        .rubric-box {
            margin-top: 7px;
            padding: 7px;
            background: #ffffff;
            border: 1px solid #e9e2f2;
            border-radius: 9px;
        }

        .rubric-title {
            font-size: 9px;
            color: #5B3E8E;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .rubric-item {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
        }

        .rubric-item td {
            border: 0 !important;
            padding: 2px 0;
            font-size: 9px;
            background: transparent !important;
        }

        .note-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            margin-left: -8px;
            margin-right: -8px;
        }

        .note-table td {
            width: 50%;
            vertical-align: top;
            padding: 10px;
            border: 1px solid #ece8f3;
            border-radius: 14px;
            background: #ffffff;
        }

        .note-table .full {
            width: 100%;
        }

        .note-title {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.35px;
            color: #5B3E8E;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .signature-table td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            padding-top: 12px;
        }

        .signature-label {
            color: #7a718d;
            font-size: 10px;
        }

        .signature-line {
            margin: 42px auto 5px auto;
            border-top: 1px solid #211936;
            width: 175px;
        }

        .mini-status {
            font-size: 9px;
            color: #7a718d;
            margin-top: 2px;
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
        <td class="doc-title">
            <h1>REPORT CARD</h1>
            <div class="meta">
                {{ $reportCard->report_no }}<br>
                {{ optional($reportCard->generated_at)->format('d M Y') ?? now()->format('d M Y') }}
            </div>
        </td>
    </tr>
</table>

<div class="hero">
    <table class="hero-table">
        <tr>
            <td>
                <div class="student-name">{{ $studentName }}</div>
                <div class="student-meta">
                    {{ $student->email ?? 'No email' }}<br>
                    {{ $reportCard->program->name ?? '-' }} - {{ $reportCard->batch->name ?? ('Batch #' . $reportCard->batch_id) }}
                </div>
            </td>
            <td class="final-score-box">
                <div class="final-score-label">Final Score</div>
                <div class="final-score">{{ number_format((float) $reportCard->final_score, 2) }}</div>
                <div>
                    <span class="grade">Grade {{ $reportCard->grade ?? '-' }}</span>
                    <span class="result-badge {{ $resultPassed ? '' : 'failed' }}">
                        {{ $resultPassed ? 'Passed' : 'Not Passed' }}
                    </span>
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Student & Program Information</div>

    <table class="info-grid">
        <tr>
            <td>
                <div class="info-label">Student Name</div>
                <div class="info-value">{{ $studentName }}</div>
            </td>
            <td>
                <div class="info-label">Email</div>
                <div class="info-value">{{ $student->email ?? '-' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="info-label">Program</div>
                <div class="info-value">{{ $reportCard->program->name ?? '-' }}</div>
            </td>
            <td>
                <div class="info-label">Batch</div>
                <div class="info-value">{{ $reportCard->batch->name ?? ('Batch #' . $reportCard->batch_id) }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="info-label">Assessment Template</div>
                <div class="info-value">{{ $reportCard->template->name ?? '-' }}</div>
            </td>
            <td>
                <div class="info-label">Report Status</div>
                <div class="info-value">{{ $statusLabel }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Performance Summary</div>

    <table class="summary-table">
        <tr>
            <td>
                <div class="summary-label">Final Score</div>
                <div class="summary-value">{{ number_format((float) $reportCard->final_score, 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Grade</div>
                <div class="summary-value">{{ $reportCard->grade ?? '-' }}</div>
            </td>
            <td>
                <div class="summary-label">Attendance</div>
                <div class="summary-value">{{ number_format((float) $reportCard->attendance_percent, 0) }}%</div>
            </td>
            <td>
                <div class="summary-label">Progress</div>
                <div class="summary-value">{{ number_format((float) $reportCard->progress_percent, 0) }}%</div>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Passing Rule Check</div>
    <div class="section-caption">Checklist kelulusan berdasarkan assessment template yang digunakan.</div>

    <table class="rules-table">
        @foreach($rules as $rule)
            <tr>
                <td style="width: 45%;">
                    <strong>{{ $rule['label'] }}</strong>
                </td>
                <td style="width: 35%;">
                    {{ $rule['value'] }}
                </td>
                <td style="width: 20%; text-align: right;">
                    @if($rule['passed'])
                        <span class="badge badge-pass">Passed</span>
                    @else
                        <span class="badge badge-fail">Not Passed</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>

    @if(!empty($ruleSnapshot['missing_required_components']))
        <div style="margin-top: 8px; color: #8a6400;">
            <strong>Missing required components:</strong>
            @foreach($ruleSnapshot['missing_required_components'] as $missing)
                {{ $missing['name'] ?? '-' }}{{ !$loop->last ? ', ' : '' }}
            @endforeach
        </div>
    @endif
</div>

<div class="section">
    <div class="section-title">Score Breakdown</div>
    <div class="section-caption">Detail nilai setiap komponen assessment dan kontribusinya ke final score.</div>

    @if(count($components))
        <table class="score-table">
            <thead>
                <tr>
                    <th style="width: 31%; text-align: left;">Component</th>
                    <th style="width: 15%;">Type</th>
                    <th style="width: 14%;" class="text-right">Raw Score</th>
                    <th style="width: 12%;" class="text-right">Weight</th>
                    <th style="width: 14%;" class="text-right">Final</th>
                    <th style="width: 14%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($components as $component)
                    <tr>
                        <td>
                            <strong>{{ $component['name'] ?? '-' }}</strong>

                            @if(!empty($component['description']))
                                <div class="muted">{{ $component['description'] }}</div>
                            @endif

                            @if(!empty($component['rubric_scores']))
                                <div class="rubric-box">
                                    <div class="rubric-title">Rubric Detail</div>

                                    <table class="rubric-item">
                                        @foreach($component['rubric_scores'] as $rubricScore)
                                            <tr>
                                                <td>
                                                    <strong>{{ $rubricScore['criteria_name'] ?? '-' }}</strong>
                                                    <div class="muted">{{ $rubricScore['note'] ?? 'No note' }}</div>
                                                </td>
                                                <td style="text-align: right; width: 75px;">
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
                            <strong>{{ number_format((float)($component['weighted_score'] ?? 0), 2) }}</strong>
                        </td>

                        <td class="text-center">
                            @if(!empty($component['has_score']))
                                <span class="badge badge-pass">Filled</span>
                            @else
                                <span class="badge badge-fail">Missing</span>
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
        <div class="soft-card">
            <span class="muted">Score snapshot is empty.</span>
        </div>
    @endif
</div>

<div class="section">
    <div class="section-title">Notes & Feedback</div>

    <table class="note-table">
        <tr>
            <td>
                <div class="note-title">Summary</div>
                {{ $reportCard->summary ?: 'No summary yet.' }}
            </td>
            <td>
                <div class="note-title">Strengths</div>
                {{ $reportCard->strengths ?: 'No strengths note yet.' }}
            </td>
        </tr>
        <tr>
            <td>
                <div class="note-title">Improvements</div>
                {{ $reportCard->improvements ?: 'No improvement note yet.' }}
            </td>
            <td>
                <div class="note-title">Instructor Note</div>
                {{ $reportCard->instructor_note ?: 'No instructor note yet.' }}
            </td>
        </tr>
        <tr>
            <td colspan="2" class="full">
                <div class="note-title">Academic Note</div>
                {{ $reportCard->academic_note ?: 'No academic note yet.' }}
            </td>
        </tr>
    </table>
</div>

<table class="signature-table">
    <tr>
        <td>
            <div class="signature-label">Academic Team</div>
            <div class="signature-line"></div>
            <strong>FlexLabs</strong>
            <div class="mini-status">Academic Evaluation</div>
        </td>
        <td>
            <div class="signature-label">Student</div>
            <div class="signature-line"></div>
            <strong>{{ $studentName }}</strong>
            <div class="mini-status">Report Card Recipient</div>
        </td>
    </tr>
</table>

</body>
</html>