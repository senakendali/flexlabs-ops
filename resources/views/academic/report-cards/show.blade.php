@extends('layouts.app-dashboard')

@section('title', 'Report Card Detail')

@section('content')
@php
    $student = $reportCard->student;

    $studentName = $student->name
        ?? $student->full_name
        ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

    if(!$studentName) {
        $studentName = $student->email ?? 'Student #' . $reportCard->student_id;
    }

    $scoreSnapshot = $reportCard->score_snapshot ?? [];
    $components = $scoreSnapshot['components'] ?? [];
    $ruleSnapshot = $reportCard->rule_snapshot ?? [];

    $statusClass = match($reportCard->status) {
        'published' => 'status-published',
        'passed' => 'status-open',
        'not_passed' => 'status-closed',
        'cancelled' => 'status-closed',
        default => 'status-draft',
    };

    $statusLabel = ucwords(str_replace('_', ' ', $reportCard->status));

    $certificate = $reportCard->certificate;
@endphp

<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Evaluation</div>
                <h1 class="page-title mb-2">Report Card Detail</h1>
                <p class="page-subtitle mb-0">
                    {{ $reportCard->report_no }} — {{ $studentName }}
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('academic.report-cards.index') }}" class="btn btn-light btn-modern">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>

                <form method="POST" action="{{ route('academic.report-cards.regenerate', $reportCard) }}" class="d-inline"
                      onsubmit="return confirm('Regenerate report card ini? Snapshot nilai akan diperbarui.');">
                    @csrf
                    <button type="submit" class="btn btn-light btn-modern">
                        <i class="bi bi-arrow-repeat me-2"></i>Regenerate
                    </button>
                </form>

                @if($reportCard->status !== 'published' && $reportCard->status !== 'cancelled')
                    <form method="POST" action="{{ route('academic.report-cards.publish', $reportCard) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-light btn-modern">
                            <i class="bi bi-send-check me-2"></i>Publish
                        </button>
                    </form>
                @endif

                @if($certificate)
                    <a href="{{ route('academic.certificates.show', $certificate) }}" class="btn btn-light btn-modern">
                        <i class="bi bi-award me-2"></i>View Certificate
                    </a>
                @elseif($reportCard->is_certificate_eligible)
                    <form method="POST" action="{{ route('academic.certificates.issue') }}" class="d-inline"
                          onsubmit="return confirm('Issue certificate untuk student ini?');">
                        @csrf
                        <input type="hidden" name="report_card_id" value="{{ $reportCard->id }}">
                        <button type="submit" class="btn btn-primary btn-modern">
                            <i class="bi bi-award me-2"></i>Issue Certificate
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-star"></i>
                    </div>
                    <div>
                        <div class="stat-title">Final Score</div>
                        <div class="stat-value">{{ number_format((float) $reportCard->final_score, 2) }}</div>
                    </div>
                </div>
                <div class="stat-description">Akumulasi weighted score semua komponen.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-bar-chart"></i>
                    </div>
                    <div>
                        <div class="stat-title">Grade</div>
                        <div class="stat-value">{{ $reportCard->grade ?? '-' }}</div>
                    </div>
                </div>
                <div class="stat-description">Grade berdasarkan final score.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Attendance</div>
                        <div class="stat-value">{{ number_format((float) $reportCard->attendance_percent, 0) }}%</div>
                    </div>
                </div>
                <div class="stat-description">Persentase kehadiran/live mentoring.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-play-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Progress</div>
                        <div class="stat-value">{{ number_format((float) $reportCard->progress_percent, 0) }}%</div>
                    </div>
                </div>
                <div class="stat-description">Progress VOD / lesson completion.</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Student Information</h5>
                        <p class="content-card-subtitle mb-0">Identitas student dan program.</p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="student-report-profile">
                        <div class="student-report-avatar">
                            {{ strtoupper(substr($studentName, 0, 1)) }}
                        </div>
                        <div>
                            <h5 class="mb-1">{{ $studentName }}</h5>
                            <div class="text-muted">{{ $student->email ?? 'No email' }}</div>
                        </div>
                    </div>

                    <div class="info-list mt-4">
                        <div class="info-list-item">
                            <span>Program</span>
                            <strong>{{ $reportCard->program->name ?? '-' }}</strong>
                        </div>
                        <div class="info-list-item">
                            <span>Batch</span>
                            <strong>{{ $reportCard->batch->name ?? ('Batch #' . $reportCard->batch_id) }}</strong>
                        </div>
                        <div class="info-list-item">
                            <span>Template</span>
                            <strong>{{ $reportCard->template->name ?? '-' }}</strong>
                        </div>
                        <div class="info-list-item">
                            <span>Status</span>
                            <span class="assignment-status-badge {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </div>
                        <div class="info-list-item">
                            <span>Certificate</span>
                            @if($certificate)
                                <span class="deadline-badge deadline-open">Issued</span>
                            @elseif($reportCard->is_certificate_eligible)
                                <span class="deadline-badge deadline-open">Eligible</span>
                            @else
                                <span class="deadline-badge deadline-closed">Not Eligible</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Passing Rule Check</h5>
                        <p class="content-card-subtitle mb-0">Syarat kelulusan berdasarkan template.</p>
                    </div>
                </div>

                <div class="content-card-body">
                    @php
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
                    @endphp

                    <div class="rule-check-list">
                        @foreach($rules as $rule)
                            <div class="rule-check-item">
                                <div>
                                    <div class="rule-check-title">{{ $rule['label'] }}</div>
                                    <div class="rule-check-value">{{ $rule['value'] }}</div>
                                </div>

                                @if($rule['passed'])
                                    <span class="rule-check-icon is-pass">
                                        <i class="bi bi-check-lg"></i>
                                    </span>
                                @else
                                    <span class="rule-check-icon is-fail">
                                        <i class="bi bi-x-lg"></i>
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if(!empty($ruleSnapshot['missing_required_components']))
                        <div class="alert alert-warning mt-3 mb-0">
                            <strong>Missing required components:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($ruleSnapshot['missing_required_components'] as $missing)
                                    <li>{{ $missing['name'] ?? '-' }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Score Breakdown</h5>
                        <p class="content-card-subtitle mb-0">
                            Breakdown nilai berdasarkan komponen assessment dan bobot.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    @if(count($components))
                        <div class="table-responsive">
                            <table class="table table-hover align-middle admin-table">
                                <thead>
                                    <tr>
                                        <th>Component</th>
                                        <th>Type</th>
                                        <th class="text-end">Raw Score</th>
                                        <th class="text-end">Weight</th>
                                        <th class="text-end">Final</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($components as $component)
                                        <tr>
                                            <td>
                                                <div class="fw-bold">{{ $component['name'] ?? '-' }}</div>
                                                <div class="text-muted small">{{ $component['description'] ?? '-' }}</div>
                                            </td>
                                            <td>
                                                <span class="deadline-badge deadline-open">
                                                    {{ ucwords(str_replace('_', ' ', $component['type'] ?? '-')) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                {{ number_format((float)($component['raw_score'] ?? 0), 2) }}
                                            </td>
                                            <td class="text-end">
                                                {{ number_format((float)($component['weight'] ?? 0), 2) }}%
                                            </td>
                                            <td class="text-end fw-bold">
                                                {{ number_format((float)($component['weighted_score'] ?? 0), 2) }}
                                            </td>
                                            <td>
                                                @if(!empty($component['has_score']))
                                                    <span class="assignment-status-badge status-published">Filled</span>
                                                @else
                                                    <span class="assignment-status-badge status-closed">Missing</span>
                                                @endif
                                            </td>
                                        </tr>

                                        @if(!empty($component['rubric_scores']))
                                            <tr class="rubric-detail-row">
                                                <td colspan="6">
                                                    <div class="rubric-detail-box">
                                                        <div class="rubric-detail-title">Rubric Detail</div>

                                                        <div class="row g-2 mt-2">
                                                            @foreach($component['rubric_scores'] as $rubricScore)
                                                                <div class="col-md-6">
                                                                    <div class="rubric-detail-item">
                                                                        <div>
                                                                            <div class="rubric-detail-name">
                                                                                {{ $rubricScore['criteria_name'] ?? '-' }}
                                                                            </div>
                                                                            <div class="rubric-detail-note">
                                                                                {{ $rubricScore['note'] ?? 'No note' }}
                                                                            </div>
                                                                        </div>
                                                                        <div class="text-end">
                                                                            <div class="fw-bold">
                                                                                {{ number_format((float)($rubricScore['raw_score'] ?? 0), 2) }}
                                                                            </div>
                                                                            <div class="text-muted small">
                                                                                {{ number_format((float)($rubricScore['weighted_score'] ?? 0), 2) }} pts
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4" class="text-end">Final Score</th>
                                        <th class="text-end">{{ number_format((float) $reportCard->final_score, 2) }}</th>
                                        <th>Grade {{ $reportCard->grade ?? '-' }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="empty-state-box">
                            <div class="empty-state-icon">
                                <i class="bi bi-bar-chart"></i>
                            </div>
                            <h5 class="empty-state-title">Score snapshot kosong</h5>
                            <p class="empty-state-text mb-0">
                                Coba regenerate report card untuk mengambil ulang data assessment score terbaru.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="content-card">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Notes & Feedback</h5>
                        <p class="content-card-subtitle mb-0">
                            Catatan yang akan dipakai untuk report card PDF nanti.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="note-box">
                                <div class="note-title">Summary</div>
                                <p class="mb-0">{{ $reportCard->summary ?: 'No summary yet.' }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="note-box">
                                <div class="note-title">Strengths</div>
                                <p class="mb-0">{{ $reportCard->strengths ?: 'No strengths note yet.' }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="note-box">
                                <div class="note-title">Improvements</div>
                                <p class="mb-0">{{ $reportCard->improvements ?: 'No improvement note yet.' }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="note-box">
                                <div class="note-title">Instructor Note</div>
                                <p class="mb-0">{{ $reportCard->instructor_note ?: 'No instructor note yet.' }}</p>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="note-box">
                                <div class="note-title">Academic Note</div>
                                <p class="mb-0">{{ $reportCard->academic_note ?: 'No academic note yet.' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap justify-content-end mt-4">
                        @if($reportCard->status !== 'cancelled')
                            <form method="POST" action="{{ route('academic.report-cards.cancel', $reportCard) }}"
                                  onsubmit="return confirm('Cancel report card ini?');">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-modern">
                                    <i class="bi bi-x-circle me-2"></i>Cancel Report
                                </button>
                            </form>
                        @endif

                        <a
                            href="{{ route('academic.report-cards.download-pdf', $reportCard) }}"
                            class="btn btn-success btn-modern"
                        >
                            <i class="bi bi-filetype-pdf me-2"></i>Download PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection