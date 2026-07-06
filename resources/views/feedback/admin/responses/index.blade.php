@extends('layouts.app-dashboard')

@section('title', 'Feedback Survey')

@section('content')
@php
    $filters = $filters ?? [];
    $summary = $summary ?? [];

    $responseItems = method_exists($responses, 'items')
        ? collect($responses->items())
        : collect($responses ?? []);

    $totalResponses = (int) data_get($summary, 'total', method_exists($responses, 'total') ? $responses->total() : $responseItems->count());
    $submittedResponses = (int) data_get($summary, 'submitted', 0);
    $draftResponses = (int) data_get($summary, 'draft', 0);

    $responseRate = $totalResponses > 0
        ? round(($submittedResponses / $totalResponses) * 100, 1)
        : 0;

    $averageScore = data_get($summary, 'average_score');
    $averageNps = data_get($summary, 'average_nps');

    $promoterCount = (int) data_get($summary, 'promoter_count', 0);
    $passiveCount = (int) data_get($summary, 'passive_count', 0);
    $detractorCount = (int) data_get($summary, 'detractor_count', 0);

    $submittedNpsCount = max(1, $promoterCount + $passiveCount + $detractorCount);

    $promoterRate = $promoterCount > 0
        ? round(($promoterCount / $submittedNpsCount) * 100, 1)
        : 0;

    $detractorRate = $detractorCount > 0
        ? round(($detractorCount / $submittedNpsCount) * 100, 1)
        : 0;

    $npsScore = data_get($summary, 'nps_score');

    if ($npsScore === null) {
        $npsScore = ($promoterCount + $passiveCount + $detractorCount) > 0
            ? round($promoterRate - $detractorRate)
            : null;
    }

    $nextProgramLeads = (int) data_get($summary, 'next_program_leads', 0);
    $interestedCount = (int) data_get($summary, 'interested_count', 0);
    $maybeInterestedCount = (int) data_get($summary, 'maybe_interested_count', 0);
    $notInterestedCount = (int) data_get($summary, 'not_interested_count', 0);

    $needsAttentionCount = (int) data_get($summary, 'needs_attention_count', 0);
    $testimonialCount = (int) data_get($summary, 'testimonial_count', 0);

    $perPageValue = (int) request('per_page', $filters['per_page'] ?? 15);

    $statusValue = (string) ($filters['status'] ?? request('status', ''));
    $nextStepValue = (string) ($filters['next_step'] ?? request('next_step', ''));
    $searchValue = (string) ($filters['search'] ?? request('search', ''));

    $scoreDisplay = $averageScore !== null
        ? number_format((float) $averageScore, 2)
        : '-';

    $averageNpsDisplay = $averageNps !== null
        ? number_format((float) $averageNps, 1)
        : '-';

    $npsScoreDisplay = $npsScore !== null
        ? (($npsScore > 0 ? '+' : '') . number_format((float) $npsScore, 0))
        : '-';

    $statusBadgeClass = function (?string $status): string {
        return match ($status) {
            'submitted' => 'bg-success-subtle text-success border border-success-subtle',
            'draft' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
        };
    };

    $statusLabel = function (?string $status): string {
        return match ($status) {
            'submitted' => 'Submitted',
            'draft' => 'Belum Submitted',
            default => 'Belum Submitted',
        };
    };

    $npsBadgeClass = function ($score): string {
        if ($score === null || $score === '') {
            return 'bg-secondary-subtle text-secondary border border-secondary-subtle';
        }

        $score = (int) $score;

        if ($score >= 9) {
            return 'bg-success-subtle text-success border border-success-subtle';
        }

        if ($score >= 7) {
            return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
        }

        return 'bg-danger-subtle text-danger border border-danger-subtle';
    };

    $npsLabel = function ($score): string {
        if ($score === null || $score === '') {
            return '-';
        }

        $score = (int) $score;

        if ($score >= 9) {
            return 'Promoter';
        }

        if ($score >= 7) {
            return 'Passive';
        }

        return 'Detractor';
    };

    $nextStepBadgeClass = function (?string $answer): string {
        $answer = \Illuminate\Support\Str::lower((string) $answer);

        if (\Illuminate\Support\Str::contains($answer, ['ya', 'tertarik'])) {
            return 'bg-success-subtle text-success border border-success-subtle';
        }

        if (\Illuminate\Support\Str::contains($answer, ['mungkin', 'ingin tahu', 'cocok'])) {
            return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
        }

        if (\Illuminate\Support\Str::contains($answer, ['belum', 'tidak'])) {
            return 'bg-secondary-subtle text-secondary border border-secondary-subtle';
        }

        return 'bg-light text-muted border';
    };

    $findAnswerByKeywords = function ($response, array $keywords): ?string {
        $answers = $response->relationLoaded('answers')
            ? $response->answers
            : $response->answers()->get();

        $answer = $answers->first(function ($answer) use ($keywords) {
            $questionText = \Illuminate\Support\Str::lower((string) $answer->question_text_snapshot);

            foreach ($keywords as $keyword) {
                if (\Illuminate\Support\Str::contains($questionText, \Illuminate\Support\Str::lower($keyword))) {
                    return true;
                }
            }

            return false;
        });

        return $answer?->display_answer ? (string) $answer->display_answer : null;
    };
@endphp

<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Feedback Management</div>
                <h1 class="page-title mb-2">Feedback Survey</h1>
                <p class="page-subtitle mb-0">
                    Pantau hasil survey student, response rate, NPS, testimonial, dan minat lanjut program FlexLabs.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                @if(Route::has('feedback.responses.index'))
                    <a href="{{ route('feedback.responses.index') }}" class="btn btn-light btn-modern">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Refresh
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

    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Survey Health</div>
        <h4 class="dashboard-section-title mb-1">Feedback Response Summary</h4>
        <p class="dashboard-section-subtitle mb-0">
            Ringkasan partisipasi feedback dan kualitas pengalaman belajar berdasarkan data survey.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-send-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Sent</div>
                        <div class="stat-value">{{ number_format($totalResponses) }}</div>
                    </div>
                </div>
                <div class="stat-description">Total link feedback yang sudah dibuat untuk student.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Submitted</div>
                        <div class="stat-value">{{ number_format($submittedResponses) }}</div>
                    </div>
                </div>
                <div class="stat-description">{{ number_format($draftResponses) }} feedback belum submitted oleh student.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-activity"></i>
                    </div>
                    <div>
                        <div class="stat-title">Response Rate</div>
                        <div class="stat-value">{{ number_format($responseRate, 1) }}%</div>
                    </div>
                </div>
                <div class="stat-description">Persentase student yang sudah mengisi survey.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-star"></i>
                    </div>
                    <div>
                        <div class="stat-title">Average Score</div>
                        <div class="stat-value">{{ $scoreDisplay }}</div>
                    </div>
                </div>
                <div class="stat-description">Rata-rata rating program dari skala 1 sampai 5.</div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Student Satisfaction</div>
        <h4 class="dashboard-section-title mb-1">Satisfaction & Loyalty</h4>
        <p class="dashboard-section-subtitle mb-0">
            Baca sinyal loyalitas student dari NPS, promoter, detractor, dan feedback yang butuh perhatian.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-megaphone"></i>
                    </div>
                    <div>
                        <div class="stat-title">NPS Score</div>
                        <div class="stat-value">{{ $npsScoreDisplay }}</div>
                    </div>
                </div>
                <div class="stat-description">Promoter rate dikurangi detractor rate.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-emoji-smile"></i>
                    </div>
                    <div>
                        <div class="stat-title">Promoters</div>
                        <div class="stat-value">{{ number_format($promoterCount) }}</div>
                    </div>
                </div>
                <div class="stat-description">{{ number_format($promoterRate, 1) }}% dari response yang punya NPS.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-emoji-frown"></i>
                    </div>
                    <div>
                        <div class="stat-title">Detractors</div>
                        <div class="stat-value">{{ number_format($detractorCount) }}</div>
                    </div>
                </div>
                <div class="stat-description">{{ number_format($detractorRate, 1) }}% perlu dicek lebih lanjut.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Needs Attention</div>
                        <div class="stat-value">{{ number_format($needsAttentionCount) }}</div>
                    </div>
                </div>
                <div class="stat-description">Overall score rendah atau NPS masuk detractor.</div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Program Continuation</div>
        <h4 class="dashboard-section-title mb-1">Next Program Opportunity</h4>
        <p class="dashboard-section-subtitle mb-0">
            Sinyal follow-up untuk student yang tertarik atau mungkin tertarik mengikuti program FlexLabs berikutnya.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="content-card h-100">
                <div class="content-card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon-wrap flex-shrink-0">
                            <i class="bi bi-arrow-up-right-circle"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark mb-1">Next Program Leads</div>
                            <div class="h4 fw-black mb-1">{{ number_format($nextProgramLeads) }}</div>
                            <div class="small text-muted">
                                Gabungan student yang menjawab “Ya” dan “Mungkin”.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="content-card h-100">
                <div class="content-card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon-wrap flex-shrink-0">
                            <i class="bi bi-person-check"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark mb-1">Interested</div>
                            <div class="h4 fw-black mb-1">{{ number_format($interestedCount) }}</div>
                            <div class="small text-muted">
                                Student yang sudah menyatakan tertarik lanjut.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="content-card h-100">
                <div class="content-card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon-wrap flex-shrink-0">
                            <i class="bi bi-question-circle"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark mb-1">Maybe Interested</div>
                            <div class="h4 fw-black mb-1">{{ number_format($maybeInterestedCount) }}</div>
                            <div class="small text-muted">
                                Student yang perlu dibantu memilih program yang cocok.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="content-card h-100">
                <div class="content-card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon-wrap flex-shrink-0">
                            <i class="bi bi-chat-quote"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark mb-1">Testimonials</div>
                            <div class="h4 fw-black mb-1">{{ number_format($testimonialCount) }}</div>
                            <div class="small text-muted">
                                Testimonial yang bisa direview untuk bahan social proof.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
   </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Feedback Action Notes</h5>
                <p class="content-card-subtitle mb-0">
                    Catatan prioritas untuk management, academic, dan sales berdasarkan kondisi survey.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                        <div class="d-flex align-items-start gap-3">
                            <div class="stat-icon-wrap flex-shrink-0">
                                <i class="bi bi-clipboard-data"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark mb-1">Survey Participation</div>
                                <div class="small text-muted">
                                    @if($totalResponses <= 0)
                                        Belum ada feedback link yang dibuat. Generate link feedback dari data student atau batch terlebih dulu.
                                    @elseif($responseRate < 50)
                                        Response rate masih rendah. Follow-up student yang belum mengisi agar data evaluasi lebih representatif.
                                    @elseif($responseRate < 75)
                                        Response rate cukup, tapi masih bisa ditingkatkan agar insight program lebih kuat.
                                    @else
                                        Response rate sudah sehat. Data survey bisa dipakai sebagai dasar evaluasi program.
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                        <div class="d-flex align-items-start gap-3">
                            <div class="stat-icon-wrap flex-shrink-0">
                                <i class="bi bi-mortarboard"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark mb-1">Program Quality</div>
                                <div class="small text-muted">
                                    @if($averageScore !== null && (float) $averageScore < 4)
                                        Average score perlu diperhatikan. Cek detail feedback pada materi, instructor, support, dan praktik.
                                    @elseif($needsAttentionCount > 0)
                                        Ada feedback yang perlu ditindaklanjuti. Prioritaskan student dengan score rendah atau NPS detractor.
                                    @elseif($averageScore !== null)
                                        Program satisfaction terlihat sehat. Pertahankan kualitas materi, instructor, dan support student.
                                    @else
                                        Belum ada score yang bisa dianalisis. Tunggu response submitted atau cek filter aktif.
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                        <div class="d-flex align-items-start gap-3">
                            <div class="stat-icon-wrap flex-shrink-0">
                                <i class="bi bi-bullseye"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark mb-1">Sales Opportunity</div>
                                <div class="small text-muted">
                                    @if($nextProgramLeads > 0)
                                        Ada {{ number_format($nextProgramLeads) }} student yang bisa difollow-up untuk program lanjutan. Prioritaskan yang menjawab “Ya” lalu “Mungkin”.
                                    @elseif($submittedResponses > 0)
                                        Belum ada sinyal kuat untuk program lanjutan. Cek testimonial dan kebutuhan belajar lanjutan untuk pendekatan yang lebih relevan.
                                    @else
                                        Opportunity sales akan terbaca setelah student mulai mengisi survey.
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
   </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Feedback Responses</h5>
                <p class="content-card-subtitle mb-0">
                    Filter berdasarkan nama/email student, status submitted, dan minat program lanjutan.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('feedback.responses.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-4 col-md-6">
                        <label for="search" class="form-label">Search Student</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            class="form-control"
                            value="{{ $searchValue }}"
                            placeholder="Cari nama, email, atau token..."
                        >
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="draft" {{ $statusValue === 'draft' ? 'selected' : '' }}>Belum Submitted</option>
                            <option value="submitted" {{ $statusValue === 'submitted' ? 'selected' : '' }}>Submitted</option>
                        </select>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <label for="next_step" class="form-label">Next Step</label>
                        <select name="next_step" id="next_step" class="form-select">
                            <option value="">All Next Step</option>
                            <option value="interested" {{ $nextStepValue === 'interested' ? 'selected' : '' }}>Interested</option>
                            <option value="maybe" {{ $nextStepValue === 'maybe' ? 'selected' : '' }}>Maybe Interested</option>
                            <option value="not_interested" {{ $nextStepValue === 'not_interested' ? 'selected' : '' }}>Not Interested</option>
                        </select>
                    </div>

                    <div class="col-xl-1 col-md-6">
                        <label for="per_page" class="form-label">Show</label>
                        <select name="per_page" id="per_page" class="form-select">
                            @foreach ([15, 25, 50, 100] as $size)
                                <option value="{{ $size }}" {{ $perPageValue === $size ? 'selected' : '' }}>
                                    {{ $size }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-12">
                        <div class="d-flex gap-2 justify-content-xl-end flex-wrap">
                            <a href="{{ route('feedback.responses.index') }}" class="btn btn-secondary btn-modern">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                            </a>

                            <button type="submit" class="btn btn-primary btn-modern">
                                <i class="bi bi-funnel me-2"></i>Filter
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
                <h5 class="content-card-title mb-1">Feedback Response List</h5>
                <p class="content-card-subtitle mb-0">
                    Klik detail untuk melihat semua jawaban survey student per section.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if($responses->count())
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap">No</th>
                                <th class="text-nowrap">Student</th>
                                <th class="text-nowrap">Form</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-end text-nowrap">Overall</th>
                                <th class="text-nowrap">NPS</th>
                                <th class="text-nowrap">Next Step</th>
                                <th class="text-nowrap">Submitted At</th>
                                <th class="text-end text-nowrap">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($responses as $response)
                                @php
                                    $rowNumber = method_exists($responses, 'currentPage')
                                        ? (($responses->currentPage() - 1) * $responses->perPage()) + $loop->iteration
                                        : $loop->iteration;

                                    $studentName = $response->student_name ?: 'Student FlexLabs';
                                    $studentEmail = $response->student_email ?: '-';

                                    $status = $response->status ?: 'draft';

                                    $overallScore = $response->overall_score !== null
                                        ? number_format((float) $response->overall_score, 2)
                                        : '-';

                                    $npsScoreValue = $response->nps_score;
                                    $npsText = $npsScoreValue !== null
                                        ? $npsScoreValue . ' · ' . $npsLabel($npsScoreValue)
                                        : '-';

                                    $nextStepAnswer = data_get($response, 'next_step_answer')
                                        ?: $findAnswerByKeywords($response, ['program lanjutan', 'program selanjutnya', 'mengikuti program lanjutan']);

                                    $submittedAt = $response->submitted_at
                                        ? $response->submitted_at->format('d M Y H:i')
                                        : '-';
                                @endphp

                                <tr>
                                    <td class="text-muted fw-semibold">
                                        {{ $rowNumber }}
                                    </td>

                                    <td>
                                        <div class="fw-bold text-dark">{{ $studentName }}</div>
                                        <div class="small text-muted">{{ $studentEmail }}</div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $response->form?->title ?: '-' }}
                                        </div>

                                        <div class="small text-muted">
                                            @if($response->program_id)
                                                Program #{{ $response->program_id }}
                                            @else
                                                General Program
                                            @endif

                                            @if($response->batch_id)
                                                · Batch #{{ $response->batch_id }}
                                            @endif
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge rounded-pill px-3 py-2 {{ $statusBadgeClass($status) }}">
                                            {{ $statusLabel($status) }}
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        <span class="fw-bold text-dark">{{ $overallScore }}</span>
                                    </td>

                                    <td>
                                        <span class="badge rounded-pill px-3 py-2 {{ $npsBadgeClass($npsScoreValue) }}">
                                            {{ $npsText }}
                                        </span>
                                    </td>

                                    <td>
                                        @if($nextStepAnswer)
                                            <span class="badge rounded-pill px-3 py-2 {{ $nextStepBadgeClass($nextStepAnswer) }}">
                                                {{ \Illuminate\Support\Str::limit($nextStepAnswer, 46) }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="small fw-semibold text-muted">{{ $submittedAt }}</span>
                                    </td>

                                    <td class="text-end">
                                        <a
                                            href="{{ route('feedback.responses.show', $response) }}"
                                            class="btn btn-sm btn-light btn-modern"
                                        >
                                            <i class="bi bi-eye me-1"></i>Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(method_exists($responses, 'hasPages') && $responses->hasPages())
                    <div class="mt-4">
                        {{ $responses->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state text-center py-5">
                    <div class="empty-state-icon mb-3">
                        <i class="bi bi-chat-square-heart"></i>
                    </div>

                    <h5 class="fw-bold mb-2">Belum ada feedback response</h5>
                    <p class="text-muted mb-0">
                        Data survey akan muncul setelah feedback link dibuat dan student mulai mengisi form.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection