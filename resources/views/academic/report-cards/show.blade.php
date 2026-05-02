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

    $canPublish = !in_array($reportCard->status, ['published', 'cancelled'], true);
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

                @if(Route::has('academic.report-cards.download-pdf'))
                    <a
                        href="{{ route('academic.report-cards.download-pdf', $reportCard) }}"
                        class="btn btn-light btn-modern"
                    >
                        <i class="bi bi-filetype-pdf me-2"></i>Download PDF
                    </a>
                @endif

                @if($reportCard->status !== 'cancelled' && $reportCard->status !== 'published')
                    <button
                        type="button"
                        class="btn btn-light btn-modern"
                        data-bs-toggle="modal"
                        data-bs-target="#regenerateReportModal"
                    >
                        <i class="bi bi-arrow-repeat me-2"></i>Regenerate
                    </button>
                @endif

                @if($canPublish)
                    <button
                        type="button"
                        class="btn btn-light btn-modern"
                        data-bs-toggle="modal"
                        data-bs-target="#publishReportModal"
                    >
                        <i class="bi bi-send-check me-2"></i>Publish
                    </button>
                @endif

                @if($certificate)
                    <a href="{{ route('academic.certificates.show', $certificate) }}" class="btn btn-light btn-modern">
                        <i class="bi bi-award me-2"></i>View Certificate
                    </a>
                @elseif($reportCard->is_certificate_eligible)
                    <button
                        type="button"
                        class="btn btn-light btn-modern"
                        data-bs-toggle="modal"
                        data-bs-target="#issueCertificateModal"
                    >
                        <i class="bi bi-award me-2"></i>Issue Certificate
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div id="reportCardInlineAlert" class="alert d-none" role="alert"></div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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
                        <div class="info-list-item">
                            <span>PDF Path</span>
                            @if($reportCard->pdf_path)
                                <span class="deadline-badge deadline-open">Available</span>
                            @else
                                <span class="deadline-badge deadline-closed">Not Generated</span>
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
                            <button
                                type="button"
                                class="btn btn-outline-danger btn-modern"
                                data-bs-toggle="modal"
                                data-bs-target="#cancelReportModal"
                            >
                                <i class="bi bi-x-circle me-2"></i>Cancel Report
                            </button>
                        @endif

                        @if(Route::has('academic.report-cards.download-pdf'))
                            <a
                                href="{{ route('academic.report-cards.download-pdf', $reportCard) }}"
                                class="btn btn-outline-secondary btn-modern"
                            >
                                <i class="bi bi-filetype-pdf me-2"></i>Download PDF
                            </a>
                        @endif

                        @if($canPublish)
                            <button
                                type="button"
                                class="btn btn-primary btn-modern"
                                data-bs-toggle="modal"
                                data-bs-target="#publishReportModal"
                            >
                                <i class="bi bi-send-check me-2"></i>Publish Report
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Publish Report Modal --}}
@if($canPublish)
    <div class="modal fade" id="publishReportModal" tabindex="-1" aria-labelledby="publishReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="publishReportModalLabel">Publish Report Card</h5>
                        <p class="text-muted mb-0">
                            Publish report card sebagai dokumen akademik final.
                        </p>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="alert alert-info mb-3">
                        Report card yang dipublish akan dianggap final dan siap digunakan untuk proses akademik berikutnya.
                        Pastikan data student, nilai, dan catatan sudah sesuai.
                    </div>

                    <div class="border rounded-3 p-3">
                        <div class="text-muted small mb-1">Report Card</div>
                        <div class="fw-bold text-dark">{{ $reportCard->report_no }}</div>

                        <div class="text-muted small mt-3 mb-1">Student</div>
                        <div class="fw-semibold text-dark">{{ $studentName }}</div>

                        <div class="text-muted small mt-3 mb-1">Final Score</div>
                        <div class="fw-semibold text-dark">
                            {{ number_format((float) $reportCard->final_score, 2) }}
                            · Grade {{ $reportCard->grade ?? '-' }}
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button
                        type="button"
                        class="btn btn-primary btn-modern"
                        id="publishReportBtn"
                        data-action="{{ route('academic.report-cards.publish', $reportCard) }}"
                    >
                        <i class="bi bi-send-check me-2"></i>Publish Report
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Regenerate Report Modal --}}
@if($reportCard->status !== 'cancelled' && $reportCard->status !== 'published')
    <div class="modal fade" id="regenerateReportModal" tabindex="-1" aria-labelledby="regenerateReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal">
                <form method="POST" action="{{ route('academic.report-cards.regenerate', $reportCard) }}">
                    @csrf

                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title" id="regenerateReportModalLabel">Regenerate Report Card</h5>
                            <p class="text-muted mb-0">
                                Perbarui snapshot nilai report card berdasarkan assessment score terbaru.
                            </p>
                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body pt-4">
                        <div class="alert alert-warning mb-3">
                            Proses ini akan mengambil ulang score terbaru dan mengganti snapshot report card saat ini.
                        </div>

                        <div class="border rounded-3 p-3">
                            <div class="text-muted small mb-1">Report Card</div>
                            <div class="fw-bold text-dark">{{ $reportCard->report_no }}</div>

                            <div class="text-muted small mt-3 mb-1">Student</div>
                            <div class="fw-semibold text-dark">{{ $studentName }}</div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-primary btn-modern">
                            <i class="bi bi-arrow-repeat me-2"></i>Regenerate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- Issue Certificate Modal --}}
@if(!$certificate && $reportCard->is_certificate_eligible)
    <div class="modal fade" id="issueCertificateModal" tabindex="-1" aria-labelledby="issueCertificateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal">
                <form
                    method="POST"
                    action="{{ route('academic.certificates.issue') }}"
                    id="issueCertificateForm"
                    data-action="{{ route('academic.certificates.issue') }}"
                >
                    @csrf
                    <input type="hidden" name="report_card_id" value="{{ $reportCard->id }}">

                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title" id="issueCertificateModalLabel">Issue Certificate</h5>
                            <p class="text-muted mb-0">
                                Buat certificate untuk student yang sudah eligible.
                            </p>
                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body pt-4">
                        <div id="issueCertificateModalAlert" class="alert d-none mb-3" role="alert"></div>

                        <div class="alert alert-info mb-3">
                            Certificate akan dibuat berdasarkan report card ini. Pastikan final score dan status eligibility sudah benar.
                        </div>

                        <div class="border rounded-3 p-3">
                            <div class="text-muted small mb-1">Student</div>
                            <div class="fw-bold text-dark">{{ $studentName }}</div>

                            <div class="text-muted small mt-3 mb-1">Final Score</div>
                            <div class="fw-semibold text-dark">
                                {{ number_format((float) $reportCard->final_score, 2) }}
                                · Grade {{ $reportCard->grade ?? '-' }}
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-primary btn-modern" id="issueCertificateSubmitBtn">
                            <i class="bi bi-award me-2"></i>Issue Certificate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- Cancel Report Modal --}}
@if($reportCard->status !== 'cancelled')
    <div class="modal fade" id="cancelReportModal" tabindex="-1" aria-labelledby="cancelReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal">
                <form method="POST" action="{{ route('academic.report-cards.cancel', $reportCard) }}">
                    @csrf

                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title" id="cancelReportModalLabel">Cancel Report Card</h5>
                            <p class="text-muted mb-0">
                                Konfirmasi sebelum membatalkan report card.
                            </p>
                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body pt-4">
                        <div class="alert alert-danger mb-3">
                            Report card yang dibatalkan tidak akan dianggap aktif untuk proses akademik berikutnya.
                        </div>

                        <div class="border rounded-3 p-3">
                            <div class="text-muted small mb-1">Report Card</div>
                            <div class="fw-bold text-dark">{{ $reportCard->report_no }}</div>

                            <div class="text-muted small mt-3 mb-1">Student</div>
                            <div class="fw-semibold text-dark">{{ $studentName }}</div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                            Keep Report
                        </button>

                        <button type="submit" class="btn btn-danger btn-modern">
                            <i class="bi bi-x-circle me-2"></i>Cancel Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = '{{ csrf_token() }}';

    function showTopHeaderLoader() {
        if (typeof window.showTopHeaderLoader === 'function') {
            window.showTopHeaderLoader();
            return;
        }

        if (typeof window.showTopLoader === 'function') {
            window.showTopLoader();
            return;
        }

        if (typeof window.showPageLoader === 'function') {
            window.showPageLoader();
            return;
        }

        if (typeof window.showGlobalLoader === 'function') {
            window.showGlobalLoader();
            return;
        }

        const possibleLoader = document.getElementById('topHeaderLoader')
            || document.getElementById('top-loader')
            || document.getElementById('globalTopLoader')
            || document.querySelector('[data-top-loader]');

        if (possibleLoader) {
            possibleLoader.classList.remove('d-none');
            possibleLoader.classList.add('show', 'active');
        }

        window.dispatchEvent(new CustomEvent('top-loader:show'));
    }

    function hideTopHeaderLoader() {
        if (typeof window.hideTopHeaderLoader === 'function') {
            window.hideTopHeaderLoader();
            return;
        }

        if (typeof window.hideTopLoader === 'function') {
            window.hideTopLoader();
            return;
        }

        if (typeof window.hidePageLoader === 'function') {
            window.hidePageLoader();
            return;
        }

        if (typeof window.hideGlobalLoader === 'function') {
            window.hideGlobalLoader();
            return;
        }

        const possibleLoader = document.getElementById('topHeaderLoader')
            || document.getElementById('top-loader')
            || document.getElementById('globalTopLoader')
            || document.querySelector('[data-top-loader]');

        if (possibleLoader) {
            possibleLoader.classList.add('d-none');
            possibleLoader.classList.remove('show', 'active');
        }

        window.dispatchEvent(new CustomEvent('top-loader:hide'));
    }

    function showInlineAlert(message, type = 'success') {
        const alertBox = document.getElementById('reportCardInlineAlert');

        if (!alertBox) {
            alert(message.replace(/<br\s*\/?>/gi, '\n'));
            return;
        }

        alertBox.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning', 'alert-info');

        if (type === 'success') {
            alertBox.classList.add('alert-success');
        } else {
            alertBox.classList.add('alert-danger');
        }

        alertBox.innerHTML = message;
    }

    function showToast(message, type = 'success') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
            return;
        }

        const toastEl = document.getElementById('appToast');

        if (!toastEl) {
            showInlineAlert(message, type);
            return;
        }

        const toastBody = toastEl.querySelector('.toast-body');

        if (toastBody) {
            toastBody.innerHTML = message;
        }

        toastEl.classList.remove('bg-success', 'bg-danger', 'text-white');

        if (type === 'success') {
            toastEl.classList.add('bg-success', 'text-white');
        } else {
            toastEl.classList.add('bg-danger', 'text-white');
        }

        const toast = bootstrap.Toast.getOrCreateInstance(toastEl, {
            delay: 3000
        });

        toast.show();
    }

    function renderValidationErrors(errors, fallbackMessage = 'Mohon cek kembali data yang dikirim.') {
        let messages = [];

        if (errors && typeof errors === 'object') {
            Object.keys(errors).forEach(function (key) {
                const value = errors[key];

                if (Array.isArray(value)) {
                    value.forEach(function (message) {
                        messages.push(message);
                    });
                } else if (value) {
                    messages.push(value);
                }
            });
        }

        return messages.length
            ? messages.join('<br>')
            : fallbackMessage;
    }

    async function parseFetchResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
            return await response.json().catch(function () {
                return {};
            });
        }

        await response.text().catch(function () {
            return '';
        });

        return {};
    }

    function showModalAlert(alertEl, message, type = 'danger') {
        if (!alertEl) {
            return;
        }

        alertEl.classList.remove(
            'd-none',
            'alert-success',
            'alert-danger',
            'alert-warning',
            'alert-info'
        );

        alertEl.classList.add(`alert-${type}`);
        alertEl.innerHTML = message;
    }

    function clearModalAlert(alertEl) {
        if (!alertEl) {
            return;
        }

        alertEl.classList.add('d-none');
        alertEl.classList.remove(
            'alert-success',
            'alert-danger',
            'alert-warning',
            'alert-info'
        );
        alertEl.innerHTML = '';
    }

    function setButtonLoading(button, isLoading, loadingText = 'Processing...') {
        if (!button) {
            return;
        }

        button.disabled = isLoading;

        if (isLoading) {
            if (!button.dataset.originalText) {
                button.dataset.originalText = button.innerHTML;
            }

            button.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                ${loadingText}
            `;
        } else {
            button.innerHTML = button.dataset.originalText || button.innerHTML;
        }
    }

    const publishBtn = document.getElementById('publishReportBtn');

    if (publishBtn) {
        publishBtn.addEventListener('click', async function () {
            const actionUrl = publishBtn.dataset.action;

            if (!actionUrl) {
                showToast('Route publish report card belum tersedia.', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('_token', csrfToken);

            setButtonLoading(publishBtn, true, 'Publishing...');
            showTopHeaderLoader();

            try {
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                const data = await parseFetchResponse(response);

                if (!response.ok) {
                    if (response.status === 422) {
                        showToast(renderValidationErrors(data.errors), 'error');
                    } else {
                        showToast(data.message || 'Gagal publish report card.', 'error');
                    }

                    setButtonLoading(publishBtn, false);
                    hideTopHeaderLoader();

                    return;
                }

                const modalEl = document.getElementById('publishReportModal');
                const modalInstance = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;

                if (modalInstance) {
                    modalInstance.hide();
                }

                showToast(data.message || 'Report card berhasil dipublish.', 'success');

                setTimeout(function () {
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        window.location.reload();
                    }
                }, 900);
            } catch (error) {
                showToast('Gagal menghubungi server.', 'error');
                setButtonLoading(publishBtn, false);
                hideTopHeaderLoader();
            }
        });
    }

    const issueCertificateForm = document.getElementById('issueCertificateForm');

    if (issueCertificateForm) {
        issueCertificateForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            const actionUrl = issueCertificateForm.dataset.action || issueCertificateForm.getAttribute('action');
            const submitBtn = document.getElementById('issueCertificateSubmitBtn');
            const modalAlert = document.getElementById('issueCertificateModalAlert');

            if (!actionUrl) {
                showModalAlert(modalAlert, 'Route issue certificate belum tersedia.', 'danger');
                showToast('Route issue certificate belum tersedia.', 'error');
                return;
            }

            const formData = new FormData(issueCertificateForm);

            if (!formData.has('_token')) {
                formData.append('_token', csrfToken);
            }

            clearModalAlert(modalAlert);
            setButtonLoading(submitBtn, true, 'Issuing...');
            showTopHeaderLoader();

            try {
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                const data = await parseFetchResponse(response);

                if (!response.ok) {
                    const message = response.status === 422
                        ? renderValidationErrors(data.errors, 'Certificate belum bisa dibuat. Mohon cek data report card.')
                        : (data.message || 'Gagal membuat certificate.');

                    showModalAlert(modalAlert, message, 'danger');
                    showToast(message, 'error');

                    setButtonLoading(submitBtn, false);
                    hideTopHeaderLoader();

                    return;
                }

                const modalEl = document.getElementById('issueCertificateModal');
                const modalInstance = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;

                if (modalInstance) {
                    modalInstance.hide();
                }

                showToast(data.message || 'Certificate berhasil dibuat.', 'success');

                setTimeout(function () {
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                        return;
                    }

                    if (data.certificate_url) {
                        window.location.href = data.certificate_url;
                        return;
                    }

                    if (response.redirected && response.url) {
                        window.location.href = response.url;
                        return;
                    }

                    window.location.reload();
                }, 700);
            } catch (error) {
                const message = 'Gagal menghubungi server saat membuat certificate.';

                showModalAlert(modalAlert, message, 'danger');
                showToast(message, 'error');

                setButtonLoading(submitBtn, false);
                hideTopHeaderLoader();
            }
        });
    }

});
</script>
@endpush