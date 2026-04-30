@extends('layouts.app-dashboard')

@section('title', 'Report Cards')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Evaluation</div>
                <h1 class="page-title mb-2">Report Cards</h1>
                <p class="page-subtitle mb-0">
                    Review hasil assessment student, publish report card, dan issue certificate jika sudah eligible.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('academic.assessment-scores.index') }}" class="btn btn-outline-primary btn-modern">
                    <i class="bi bi-pencil-square me-2"></i>Assessment Scores
                </a>

                @if(Route::has('academic.certificates.index'))
                    <a href="{{ route('academic.certificates.index') }}" class="btn btn-primary btn-modern">
                        <i class="bi bi-award me-2"></i>Certificates
                    </a>
                @endif
            </div>
        </div>
    </div>

    @php
        $totalReports = $reportCards->total();
        $publishedCount = collect($reportCards->items())->where('status', 'published')->count();
        $eligibleCount = collect($reportCards->items())->where('is_certificate_eligible', true)->count();
        $passedCount = collect($reportCards->items())->whereIn('status', ['passed', 'published'])->count();
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div>
                        <div class="stat-title">Report Cards</div>
                        <div class="stat-value">{{ $totalReports }}</div>
                    </div>
                </div>
                <div class="stat-description">Total report card sesuai filter.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Passed</div>
                        <div class="stat-value">{{ $passedCount }}</div>
                    </div>
                </div>
                <div class="stat-description">Report card dengan status lulus/published.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-award"></i>
                    </div>
                    <div>
                        <div class="stat-title">Eligible</div>
                        <div class="stat-value">{{ $eligibleCount }}</div>
                    </div>
                </div>
                <div class="stat-description">Student yang eligible untuk certificate.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-send-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Published</div>
                        <div class="stat-value">{{ $publishedCount }}</div>
                    </div>
                </div>
                <div class="stat-description">Report card yang sudah dipublish.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Report Cards</h5>
                <p class="content-card-subtitle mb-0">
                    Filter berdasarkan batch dan status report card.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('academic.report-cards.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-5 col-md-6">
                        <label class="form-label">Batch</label>
                        <select name="batch_id" class="form-select">
                            <option value="">All Batches</option>
                            @foreach($batches ?? [] as $batch)
                                <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>
                                    {{ $batch->program->name ?? 'Program' }} - {{ $batch->name ?? ('Batch #' . $batch->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="passed" {{ request('status') === 'passed' ? 'selected' : '' }}>Passed</option>
                            <option value="not_passed" {{ request('status') === 'not_passed' ? 'selected' : '' }}>Not Passed</option>
                            <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div class="col-xl-4 col-md-12">
                        <div class="d-flex gap-2 justify-content-xl-end flex-wrap">
                            <a href="{{ route('academic.report-cards.index') }}" class="btn btn-outline-secondary btn-modern">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                            </a>
                            <button type="submit" class="btn btn-primary btn-modern">
                                <i class="bi bi-funnel me-2"></i>Apply Filter
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Report Card List</h5>
                <p class="content-card-subtitle mb-0">
                    Klik detail untuk melihat breakdown nilai, rule kelulusan, dan action certificate.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if($reportCards->count())
                <div class="table-responsive">
                    <table class="table table-hover align-middle admin-table">
                        <thead>
                            <tr>
                                <th>Report No</th>
                                <th>Student</th>
                                <th>Program / Batch</th>
                                <th class="text-end">Final Score</th>
                                <th>Grade</th>
                                <th>Status</th>
                                <th>Certificate</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportCards as $reportCard)
                                @php
                                    $student = $reportCard->student;
                                    $studentName = $student->name
                                        ?? $student->full_name
                                        ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

                                    if(!$studentName) {
                                        $studentName = $student->email ?? 'Student #' . $reportCard->student_id;
                                    }

                                    $statusClass = match($reportCard->status) {
                                        'published' => 'status-published',
                                        'passed' => 'status-open',
                                        'not_passed' => 'status-closed',
                                        'cancelled' => 'status-closed',
                                        default => 'status-draft',
                                    };

                                    $statusLabel = ucwords(str_replace('_', ' ', $reportCard->status));
                                @endphp

                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $reportCard->report_no }}</div>
                                        <div class="text-muted small">
                                            {{ optional($reportCard->generated_at)->format('d M Y H:i') ?? '-' }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="table-avatar">
                                                {{ strtoupper(substr($studentName, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $studentName }}</div>
                                                <div class="text-muted small">{{ $student->email ?? 'No email' }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold">{{ $reportCard->program->name ?? 'Program' }}</div>
                                        <div class="text-muted small">
                                            {{ $reportCard->batch->name ?? ('Batch #' . $reportCard->batch_id) }}
                                        </div>
                                    </td>

                                    <td class="text-end">
                                        <div class="fw-bold">{{ number_format((float) $reportCard->final_score, 2) }}</div>
                                        <div class="text-muted small">
                                            Attendance {{ number_format((float) $reportCard->attendance_percent, 0) }}%
                                        </div>
                                    </td>

                                    <td>
                                        <span class="grade-badge grade-{{ strtolower($reportCard->grade ?? 'e') }}">
                                            {{ $reportCard->grade ?? '-' }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="assignment-status-badge {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>

                                    <td>
                                        @if($reportCard->certificate)
                                            <span class="deadline-badge deadline-open">
                                                <i class="bi bi-award me-1"></i>Issued
                                            </span>
                                        @elseif($reportCard->is_certificate_eligible)
                                            <span class="deadline-badge deadline-open">
                                                Eligible
                                            </span>
                                        @else
                                            <span class="deadline-badge deadline-closed">
                                                Not Eligible
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2 flex-wrap justify-content-end">
                                            <a href="{{ route('academic.report-cards.show', $reportCard) }}"
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-eye me-1"></i>Detail
                                            </a>

                                            @if($reportCard->status !== 'published' && $reportCard->status !== 'cancelled')
                                                <form method="POST" action="{{ route('academic.report-cards.publish', $reportCard) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-success btn-sm">
                                                        <i class="bi bi-send-check me-1"></i>Publish
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $reportCards->links() }}
                </div>
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <h5 class="empty-state-title">Belum ada report card</h5>
                    <p class="empty-state-text mb-0">
                        Generate report card dari halaman Assessment Scores setelah nilai student diinput.
                    </p>

                    <div class="mt-3">
                        <a href="{{ route('academic.assessment-scores.index') }}" class="btn btn-primary btn-modern">
                            <i class="bi bi-pencil-square me-2"></i>Go to Assessment Scores
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection