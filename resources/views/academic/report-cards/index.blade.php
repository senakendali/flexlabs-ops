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
                <a href="{{ route('academic.assessment-scores.index') }}" class="btn btn-light btn-modern">
                    <i class="bi bi-pencil-square me-2"></i>Assessment Scores
                </a>

                @if(Route::has('academic.certificates.index'))
                    <a href="{{ route('academic.certificates.index') }}" class="btn btn-light btn-modern">
                        <i class="bi bi-award me-2"></i>Certificates
                    </a>
                @endif
            </div>
        </div>
    </div>

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
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th>Report No</th>
                                <th>Student</th>
                                <th>Program / Batch</th>
                                <th class="text-end text-nowrap">Final Score</th>
                                <th class="text-nowrap">Grade</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-nowrap">Certificate</th>
                                <th class="text-end text-nowrap">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($reportCards as $reportCard)
                                @php
                                    $student = $reportCard->student;

                                    $studentName = $student->name
                                        ?? $student->full_name
                                        ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

                                    if (!$studentName) {
                                        $studentName = $student->email ?? 'Student #' . $reportCard->student_id;
                                    }

                                    $statusClass = match ($reportCard->status) {
                                        'published' => 'status-published',
                                        'passed' => 'status-open',
                                        'not_passed' => 'status-closed',
                                        'cancelled' => 'status-closed',
                                        default => 'status-draft',
                                    };

                                    $statusLabel = ucwords(str_replace('_', ' ', $reportCard->status));

                                    $canPublish = !in_array($reportCard->status, ['published', 'cancelled'], true);
                                    $canCancel = !in_array($reportCard->status, ['cancelled', 'published'], true);
                                    $canRegenerate = !in_array($reportCard->status, ['published', 'cancelled'], true);
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

                                    <td class="text-end text-nowrap">
                                        <div class="fw-bold">{{ number_format((float) $reportCard->final_score, 2) }}</div>
                                        <div class="text-muted small">
                                            Attendance {{ number_format((float) $reportCard->attendance_percent, 0) }}%
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="grade-badge grade-{{ strtolower($reportCard->grade ?? 'e') }}">
                                            {{ $reportCard->grade ?? '-' }}
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="assignment-status-badge {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
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

                                    <td class="text-end text-nowrap">
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-sm btn-outline-secondary dropdown-toggle px-3"
                                                type="button"
                                                data-bs-toggle="dropdown"
                                                data-bs-boundary="viewport"
                                                aria-expanded="false"
                                            >
                                                Actions
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <a
                                                        href="{{ route('academic.report-cards.show', $reportCard) }}"
                                                        class="dropdown-item"
                                                    >
                                                        <i class="bi bi-eye me-2"></i>Show Detail
                                                    </a>
                                                </li>

                                                @if($canRegenerate && Route::has('academic.report-cards.regenerate'))
                                                    <li>
                                                        <form
                                                            method="POST"
                                                            action="{{ route('academic.report-cards.regenerate', $reportCard) }}"
                                                            class="m-0"
                                                        >
                                                            @csrf

                                                            <button type="submit" class="dropdown-item">
                                                                <i class="bi bi-arrow-clockwise me-2"></i>Regenerate
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif

                                                @if($canPublish)
                                                    <li>
                                                        <form
                                                            method="POST"
                                                            action="{{ route('academic.report-cards.publish', $reportCard) }}"
                                                            class="m-0"
                                                        >
                                                            @csrf

                                                            <button type="submit" class="dropdown-item text-success">
                                                                <i class="bi bi-send-check me-2"></i>Publish
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif

                                                @if($canCancel && Route::has('academic.report-cards.cancel'))
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>

                                                    <li>
                                                        <form
                                                            method="POST"
                                                            action="{{ route('academic.report-cards.cancel', $reportCard) }}"
                                                            class="m-0"
                                                            onsubmit="return confirm('Cancel report card ini?')"
                                                        >
                                                            @csrf

                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="bi bi-x-circle me-2"></i>Cancel
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
                                            </ul>
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