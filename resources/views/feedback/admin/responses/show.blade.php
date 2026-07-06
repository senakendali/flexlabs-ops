@extends('layouts.app-dashboard')

@section('title', 'Detail Feedback Survey')

@section('content')
@php
    $answers = $answers ?? collect();
    $nextStepAnswers = $nextStepAnswers ?? collect();

    $displayAnswer = function ($answer): string {
        $value = $answer->display_answer ?? null;

        if (is_array($value)) {
            return collect($value)
                ->filter(fn ($item) => ! blank($item))
                ->map(fn ($item) => is_array($item) ? json_encode($item) : (string) $item)
                ->implode(', ');
        }

        if (! blank($value)) {
            return (string) $value;
        }

        if (! blank($answer->answer_text)) {
            return (string) $answer->answer_text;
        }

        if (! is_null($answer->answer_number)) {
            return rtrim(rtrim((string) $answer->answer_number, '0'), '.');
        }

        if (! blank($answer->answer_value)) {
            return (string) $answer->answer_value;
        }

        if (is_array($answer->answer_json)) {
            return collect($answer->answer_json)
                ->filter(fn ($item) => ! blank($item))
                ->map(fn ($item) => is_array($item) ? json_encode($item) : (string) $item)
                ->implode(', ');
        }

        return '-';
    };

    $sectionIcon = function (?string $section): string {
        return match ($section) {
            'Program' => 'bi bi-mortarboard',
            'Materi' => 'bi bi-journal-text',
            'Instructor' => 'bi bi-person-video3',
            'Praktik' => 'bi bi-laptop',
            'Platform' => 'bi bi-window-sidebar',
            'Support' => 'bi bi-headset',
            'Outcome' => 'bi bi-graph-up-arrow',
            'NPS' => 'bi bi-megaphone',
            'Insight' => 'bi bi-lightbulb',
            'Testimonial' => 'bi bi-chat-quote',
            'Next Step' => 'bi bi-arrow-up-right-circle',
            default => 'bi bi-ui-checks',
        };
    };

    $statusBadgeClass = function (?string $status): string {
        return match ($status) {
            'submitted' => 'bg-success-subtle text-success border border-success-subtle',
            'draft' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
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

    $questionTypeLabel = function (?string $type): string {
        return match ($type) {
            'rating_1_5' => 'Rating 1–5',
            'rating_0_10' => 'NPS 0–10',
            'textarea' => 'Long Answer',
            'text' => 'Short Answer',
            'single_choice' => 'Single Choice',
            'checkbox' => 'Checkbox',
            default => \Illuminate\Support\Str::headline((string) $type),
        };
    };

    $nextStepMainAnswer = $nextStepAnswers->first();

    $overallScore = $response->overall_score !== null
        ? number_format((float) $response->overall_score, 2)
        : '-';

    $npsScore = $response->nps_score;
    $npsDisplay = $npsScore !== null
        ? $npsScore . ' · ' . $npsLabel($npsScore)
        : '-';

    $status = $response->status ?: 'draft';

    $isNeedsAttention = false;

    if ($response->overall_score !== null && (float) $response->overall_score <= 3) {
        $isNeedsAttention = true;
    }

    if ($response->nps_score !== null && (int) $response->nps_score <= 6) {
        $isNeedsAttention = true;
    }
@endphp

<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Feedback Management</div>

                <h1 class="page-title mb-2">Detail Feedback Survey</h1>

                <p class="page-subtitle mb-0">
                    Lihat jawaban lengkap student, score program, NPS, testimonial, dan minat program lanjutan.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('feedback.responses.index') }}" class="btn btn-light btn-modern">
                    <i class="bi bi-arrow-left me-2"></i>Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Student</div>
                        <div class="stat-value stat-value-sm">
                            {{ $response->student_name ?: 'Student FlexLabs' }}
                        </div>
                    </div>
                </div>
                <div class="stat-description">
                    {{ $response->student_email ?: 'Email tidak tersedia' }}
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-star"></i>
                    </div>
                    <div>
                        <div class="stat-title">Overall Score</div>
                        <div class="stat-value">{{ $overallScore }}</div>
                    </div>
                </div>
                <div class="stat-description">Rata-rata rating program dari skala 1 sampai 5.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-megaphone"></i>
                    </div>
                    <div>
                        <div class="stat-title">NPS</div>
                        <div class="stat-value stat-value-sm">{{ $npsDisplay }}</div>
                    </div>
                </div>
                <div class="stat-description">Indikator kemungkinan student merekomendasikan FlexLabs.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Status</div>
                        <div class="stat-value stat-value-sm">
                            <span class="badge rounded-pill px-3 py-2 {{ $statusBadgeClass($status) }}">
                                {{ ucfirst($status) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="stat-description">
                    Submitted at:
                    {{ $response->submitted_at ? $response->submitted_at->format('d M Y H:i') : '-' }}
                </div>
            </div>
        </div>
    </div>

    @if($isNeedsAttention)
        <div class="alert alert-warning border-0 rounded-4 mb-4" role="alert">
            <div class="d-flex align-items-start gap-3">
                <div class="fs-4">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>

                <div>
                    <div class="fw-bold mb-1">Feedback ini perlu diperhatikan</div>
                    <div class="small mb-0">
                        Student memberikan overall score rendah atau NPS masuk kategori detractor.
                        Cek bagian insight dan perbaikan untuk menentukan follow-up yang tepat.
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($nextStepAnswers->count())
        <div class="content-card mb-4">
            <div class="content-card-header">
                <div>
                    <h5 class="content-card-title mb-1">Next Program Signal</h5>
                    <p class="content-card-subtitle mb-0">
                        Ringkasan minat student untuk mengikuti program lanjutan di FlexLabs.
                    </p>
                </div>

                <div class="d-none d-md-flex">
                    <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                        <i class="bi bi-arrow-up-right-circle me-1"></i>
                        Sales Follow-up Signal
                    </span>
                </div>
            </div>

            <div class="content-card-body">
                <div class="row g-3">
                    @foreach($nextStepAnswers as $answer)
                        <div class="col-lg-6">
                            <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="stat-icon-wrap flex-shrink-0">
                                        <i class="bi bi-arrow-up-right-circle"></i>
                                    </div>

                                    <div>
                                        <div class="fw-bold text-dark mb-2">
                                            {{ $answer->question_text_snapshot }}
                                        </div>

                                        <div class="answer-highlight">
                                            {{ $displayAnswer($answer) }}
                                        </div>

                                        <div class="mt-2 small text-muted">
                                            {{ $questionTypeLabel($answer->question_type_snapshot) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($nextStepMainAnswer)
                    <div class="mt-3 small text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Prioritaskan follow-up untuk student yang menjawab tertarik atau ingin tahu program yang cocok.
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Student & Form Info</h5>
                        <p class="content-card-subtitle mb-0">
                            Informasi dasar response survey.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="info-list">
                        <div class="info-list-item">
                            <div class="info-label">Student Name</div>
                            <div class="info-value">{{ $response->student_name ?: '-' }}</div>
                        </div>

                        <div class="info-list-item">
                            <div class="info-label">Student Email</div>
                            <div class="info-value">{{ $response->student_email ?: '-' }}</div>
                        </div>

                        <div class="info-list-item">
                            <div class="info-label">Feedback Form</div>
                            <div class="info-value">{{ $response->form?->title ?: '-' }}</div>
                        </div>

                        <div class="info-list-item">
                            <div class="info-label">Program</div>
                            <div class="info-value">
                                {{ $response->program_id ? 'Program #' . $response->program_id : 'General Program' }}
                            </div>
                        </div>

                        <div class="info-list-item">
                            <div class="info-label">Batch</div>
                            <div class="info-value">
                                {{ $response->batch_id ? 'Batch #' . $response->batch_id : '-' }}
                            </div>
                        </div>

                        <div class="info-list-item">
                            <div class="info-label">Instructor</div>
                            <div class="info-value">
                                {{ $response->instructor_id ? 'Instructor #' . $response->instructor_id : '-' }}
                            </div>
                        </div>

                        <div class="info-list-item">
                            <div class="info-label">Token</div>
                            <div class="info-value text-break">{{ $response->token ?: '-' }}</div>
                        </div>

                        <div class="info-list-item">
                            <div class="info-label">Created At</div>
                            <div class="info-value">
                                {{ $response->created_at ? $response->created_at->format('d M Y H:i') : '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Management Reading</h5>
                        <p class="content-card-subtitle mb-0">
                            Cara cepat membaca feedback ini.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="d-grid gap-3">
                        <div class="border rounded-3 p-3 bg-light-subtle">
                            <div class="fw-bold text-dark mb-1">Academic Focus</div>
                            <div class="small text-muted">
                                Cek bagian Program, Materi, Instructor, Praktik, Support, Outcome, dan Insight untuk bahan evaluasi kelas.
                            </div>
                        </div>

                        <div class="border rounded-3 p-3 bg-light-subtle">
                            <div class="fw-bold text-dark mb-1">Sales Focus</div>
                            <div class="small text-muted">
                                Cek NPS, testimonial, dan Next Step untuk menentukan apakah student cocok difollow-up ke program lanjutan.
                            </div>
                        </div>

                        <div class="border rounded-3 p-3 bg-light-subtle">
                            <div class="fw-bold text-dark mb-1">Marketing Focus</div>
                            <div class="small text-muted">
                                Cek testimonial dan bagian terbaik dari program untuk potensi bahan social proof.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="dashboard-section-label mb-3">
                <div class="dashboard-section-eyebrow">Survey Answers</div>
                <h4 class="dashboard-section-title mb-1">Jawaban Feedback per Section</h4>
                <p class="dashboard-section-subtitle mb-0">
                    Semua jawaban student dikelompokkan berdasarkan section pertanyaan.
                </p>
            </div>

            @forelse($answers as $section => $sectionAnswers)
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <div class="d-flex align-items-start gap-3">
                            <div class="stat-icon-wrap flex-shrink-0">
                                <i class="{{ $sectionIcon($section) }}"></i>
                            </div>

                            <div>
                                <h5 class="content-card-title mb-1">{{ $section }}</h5>
                                <p class="content-card-subtitle mb-0">
                                    {{ $sectionAnswers->count() }} jawaban pada section ini.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="d-grid gap-3">
                            @foreach($sectionAnswers as $answer)
                                @php
                                    $answerText = $displayAnswer($answer);
                                    $type = $answer->question_type_snapshot;
                                    $isRating = in_array($type, ['rating_1_5', 'rating_0_10'], true);
                                    $ratingMax = $type === 'rating_0_10' ? 10 : 5;
                                    $ratingNumber = $answer->answer_number !== null ? (float) $answer->answer_number : null;
                                    $ratingPercentage = ($isRating && $ratingNumber !== null && $ratingMax > 0)
                                        ? min(100, max(0, ($ratingNumber / $ratingMax) * 100))
                                        : null;
                                @endphp

                                <div class="answer-card">
                                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                                        <div class="answer-question">
                                            {{ $answer->question_text_snapshot }}
                                        </div>

                                        <span class="badge rounded-pill bg-light text-muted border px-3 py-2">
                                            {{ $questionTypeLabel($type) }}
                                        </span>
                                    </div>

                                    @if($isRating)
                                        <div class="d-flex align-items-center gap-3 flex-wrap">
                                            <div class="rating-number">
                                                {{ $answerText }}
                                            </div>

                                            <div class="rating-track flex-grow-1">
                                                <div
                                                    class="rating-fill"
                                                    style="width: {{ $ratingPercentage ?? 0 }}%;"
                                                ></div>
                                            </div>

                                            <div class="small text-muted fw-semibold">
                                                / {{ $ratingMax }}
                                            </div>
                                        </div>
                                    @else
                                        <div class="answer-value">
                                            {!! nl2br(e($answerText)) !!}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="content-card">
                    <div class="content-card-body text-center py-5">
                        <div class="empty-state-icon mb-3">
                            <i class="bi bi-chat-square-heart"></i>
                        </div>

                        <h5 class="fw-bold mb-2">Belum ada jawaban survey</h5>

                        <p class="text-muted mb-0">
                            Response ini masih draft atau student belum mengirim feedback.
                        </p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .stat-value-sm {
        font-size: 1.05rem;
        line-height: 1.25;
    }

    .info-list {
        display: grid;
        gap: 1rem;
    }

    .info-list-item {
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.18);
    }

    .info-list-item:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .info-label {
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: .35rem;
    }

    .info-value {
        font-size: .9rem;
        font-weight: 700;
        color: #0f172a;
    }

    .answer-card {
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 1.15rem;
        background: #ffffff;
        padding: 1.1rem;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.035);
    }

    .answer-question {
        max-width: 720px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.5;
    }

    .answer-value {
        border-radius: 1rem;
        background: #f8fafc;
        border: 1px solid rgba(148, 163, 184, 0.18);
        padding: 1rem;
        font-size: .92rem;
        font-weight: 600;
        color: #334155;
        line-height: 1.75;
        white-space: normal;
    }

    .answer-highlight {
        display: inline-flex;
        width: fit-content;
        max-width: 100%;
        border-radius: 999px;
        background: rgba(91, 62, 142, 0.08);
        border: 1px solid rgba(91, 62, 142, 0.12);
        color: #5B3E8E;
        padding: .55rem .9rem;
        font-size: .88rem;
        font-weight: 800;
        line-height: 1.45;
    }

    .rating-number {
        min-width: 3rem;
        height: 3rem;
        border-radius: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(91, 62, 142, 0.08);
        color: #5B3E8E;
        font-size: 1.15rem;
        font-weight: 900;
    }

    .rating-track {
        min-width: 140px;
        height: .75rem;
        overflow: hidden;
        border-radius: 999px;
        background: #e2e8f0;
    }

    .rating-fill {
        height: 100%;
        border-radius: inherit;
        background: #5B3E8E;
    }

    .empty-state-icon {
        width: 4rem;
        height: 4rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 1.35rem;
        background: rgba(91, 62, 142, 0.08);
        color: #5B3E8E;
        font-size: 1.75rem;
    }
</style>
@endpush