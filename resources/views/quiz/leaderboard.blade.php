@extends('layouts.app-dashboard')

@section('title', 'Quiz Leaderboard')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">
        <div>
            <span class="badge rounded-pill text-bg-light border mb-2">Quiz Leaderboard</span>
            <h4 class="mb-1">{{ $quiz->title }}</h4>
            <p class="text-muted mb-0">
                Ranking peserta berdasarkan skor tertinggi, progres jawaban, dan waktu submit tercepat.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
            <a href="{{ route('quiz.questions.index', $quiz->id) }}" class="btn btn-outline-primary">
                <i class="bi bi-list-check me-1"></i> Manage Questions
            </a>
            <a href="{{ route('quiz.index') }}" class="btn btn-primary">
                <i class="bi bi-arrow-left me-1"></i> Back to Quiz List
            </a>
        </div>
    </div>

   <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm leaderboard-stat-box h-100">
                <div class="card-body">
                    <div class="leaderboard-stat-topbar leaderboard-stat-topbar-participants"></div>

                    <div class="leaderboard-stat-head">
                        <div class="leaderboard-stat-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>

                        <div class="leaderboard-stat-content">
                            <div class="leaderboard-stat-title">Total Participants</div>
                            <div class="leaderboard-stat-value">
                                {{ number_format($stats['total_participants'] ?? 0) }}
                            </div>
                        </div>
                    </div>

                    <div class="leaderboard-stat-caption">
                        Total peserta yang sudah mengerjakan quiz ini.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm leaderboard-stat-box h-100">
                <div class="card-body">
                    <div class="leaderboard-stat-topbar leaderboard-stat-topbar-questions"></div>

                    <div class="leaderboard-stat-head">
                        <div class="leaderboard-stat-icon">
                            <i class="bi bi-patch-question-fill"></i>
                        </div>

                        <div class="leaderboard-stat-content">
                            <div class="leaderboard-stat-title">Total Questions</div>
                            <div class="leaderboard-stat-value">
                                {{ number_format($stats['total_questions'] ?? 0) }}
                            </div>
                        </div>
                    </div>

                    <div class="leaderboard-stat-caption">
                        Jumlah soal aktif yang tersedia di quiz ini.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm leaderboard-stat-box h-100">
                <div class="card-body">
                    <div class="leaderboard-stat-topbar leaderboard-stat-topbar-score"></div>

                    <div class="leaderboard-stat-head">
                        <div class="leaderboard-stat-icon">
                            <i class="bi bi-trophy-fill"></i>
                        </div>

                        <div class="leaderboard-stat-content">
                            <div class="leaderboard-stat-title">Highest Score</div>
                            <div class="leaderboard-stat-value">
                                {{ number_format($stats['highest_score'] ?? 0) }}
                                <span class="leaderboard-stat-unit">/100</span>
                            </div>
                        </div>
                    </div>

                    <div class="leaderboard-stat-caption">
                        Nilai tertinggi yang berhasil dicapai peserta.
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $topThree = $participants->getCollection()->take(3);
    @endphp

    @if ($topThree->count() > 0)
        <div class="card border-0 shadow-sm leaderboard-podium-card mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-stars text-warning"></i>
                    <h5 class="mb-0">Top Performers</h5>
                </div>

                <div class="row g-3">
                    @foreach ($topThree as $item)
                        @php
                            $medalClass = match($item->rank) {
                                1 => 'gold',
                                2 => 'silver',
                                default => 'bronze',
                            };

                            $medalIcon = match($item->rank) {
                                1 => 'bi-trophy-fill',
                                2 => 'bi-award-fill',
                                default => 'bi-patch-check-fill',
                            };
                        @endphp

                        <div class="col-lg-4 col-md-6">
                            <div class="podium-item {{ $medalClass }}">
                                <div class="podium-badge">
                                    <i class="bi {{ $medalIcon }}"></i>
                                </div>

                                <div class="podium-rank">#{{ $item->rank }}</div>
                                <div class="podium-name">{{ $item->name ?: '-' }}</div>

                                <div class="podium-meta">
                                    {{ $item->email ?: ($item->phone ?: 'No contact info') }}
                                </div>

                                <div class="row g-2 mt-2">
                                    <div class="col-6">
                                        <div class="podium-mini-stat">
                                            <small>Score</small>
                                            <strong>{{ $item->score }}/100</strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="podium-mini-stat">
                                            <small>Answered</small>
                                            <strong>{{ $item->answered_questions }}/{{ $item->total_questions }}</strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <div
                                        class="progress leaderboard-progress"
                                        role="progressbar"
                                        aria-valuenow="{{ $item->score }}"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                    >
                                        <div class="progress-bar" style="width: {{ $item->score }}%"></div>
                                    </div>
                                    <div class="text-muted small mt-2">
                                        Accuracy Score: {{ $item->score }}%
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="mb-1">Participant Ranking</h5>
                <p class="text-muted small mb-0">
                    Diurutkan berdasarkan skor tertinggi, progres jawaban, lalu waktu submit tercepat.
                </p>
            </div>

            <form method="GET" class="d-flex align-items-center gap-2">
                <label for="per_page" class="form-label mb-0 small text-muted">Show</label>
                <select
                    name="per_page"
                    id="per_page"
                    class="form-select form-select-sm"
                    style="width: auto;"
                    onchange="this.form.submit()"
                >
                    @foreach ([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>
                            {{ $size }}
                        </option>
                    @endforeach
                </select>
                <span class="small text-muted">entries</span>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 leaderboard-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 90px;">Rank</th>
                        <th>Participant</th>
                        <th style="width: 180px;">Contact</th>
                        <th style="width: 150px;">Score</th>
                        <th style="width: 180px;">Answered</th>
                        <th style="width: 180px;">Accuracy</th>
                        <th style="width: 180px;">Submitted At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($participants as $participant)
                        <tr>
                            <td>
                                @if ($participant->rank === 1)
                                    <span class="leaderboard-rank-badge first">#1</span>
                                @elseif ($participant->rank === 2)
                                    <span class="leaderboard-rank-badge second">#2</span>
                                @elseif ($participant->rank === 3)
                                    <span class="leaderboard-rank-badge third">#3</span>
                                @else
                                    <span class="leaderboard-rank-text">#{{ $participant->rank }}</span>
                                @endif
                            </td>

                            <td>
                                <div class="fw-semibold">{{ $participant->name ?: '-' }}</div>
                                @if ($participant->email)
                                    <div class="small text-muted">{{ $participant->email }}</div>
                                @endif
                            </td>

                            <td>
                                @if ($participant->phone)
                                    <div class="small">{{ $participant->phone }}</div>
                                @elseif ($participant->email)
                                    <div class="small text-muted">{{ $participant->email }}</div>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>

                            <td>
                                <div class="fw-semibold text-dark">{{ $participant->score }}/100</div>
                                <div class="small text-muted">Final score</div>
                            </td>

                            <td>
                                <div class="fw-semibold">{{ $participant->answered_questions }}/{{ $participant->total_questions }}</div>
                                <div
                                    class="progress leaderboard-progress mt-2"
                                    role="progressbar"
                                    aria-valuenow="{{ $participant->answered_percentage ?? 0 }}"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                >
                                    <div class="progress-bar" style="width: {{ $participant->answered_percentage ?? 0 }}%"></div>
                                </div>
                            </td>

                            <td>
                                <span class="badge leaderboard-percent-badge">
                                    {{ $participant->score }}%
                                </span>
                            </td>

                            <td>
                                @if ($participant->created_at)
                                    <div class="fw-semibold">{{ \Carbon\Carbon::parse($participant->created_at)->format('d M Y') }}</div>
                                    <div class="small text-muted">{{ \Carbon\Carbon::parse($participant->created_at)->format('H:i') }}</div>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center justify-content-center gap-2 text-muted">
                                    <i class="bi bi-trophy fs-1"></i>
                                    <div class="fw-semibold">Belum ada peserta di leaderboard</div>
                                    <small>Data ranking akan tampil setelah ada peserta yang mengerjakan quiz.</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($participants->hasPages())
            <div class="card-footer bg-white">
                {{ $participants->links() }}
            </div>
        @endif
    </div>
</div>
@endsection