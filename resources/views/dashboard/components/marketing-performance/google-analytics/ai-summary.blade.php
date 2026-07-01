@php
    $gaAiSummary = is_array($gaAiSummary ?? null) ? $gaAiSummary : [];
    $gaSummaryText = trim((string) ($gaSummaryText ?? ''));

    $gaAiMainSummary = trim((string) ($gaAiSummary['summary'] ?? ''));
    $gaAiMainBottleneck = trim((string) ($gaAiSummary['main_bottleneck'] ?? ''));

    $gaAiPriorityFocus = collect($gaAiSummary['priority_focus'] ?? [])
        ->map(fn ($item) => trim((string) $item))
        ->filter()
        ->values();

    $gaAiRecommendedActions = collect($gaAiSummary['recommended_actions'] ?? [])
        ->map(fn ($item) => trim((string) $item))
        ->filter()
        ->values();

    $gaAiRisks = collect($gaAiSummary['risks'] ?? [])
        ->map(fn ($item) => trim((string) $item))
        ->filter()
        ->values();

    $gaAiConfidence = strtolower((string) ($gaAiSummary['confidence'] ?? 'medium'));
    $gaAiModel = $gaAiSummary['model'] ?? null;
    $gaAiGeneratedAt = $gaAiSummary['generated_at'] ?? null;
    $gaAiSource = $gaAiSummary['source'] ?? null;

    $gaHasAiSummary = $gaAiMainSummary !== '';

    $gaConfidenceBadgeClass = match ($gaAiConfidence) {
        'high' => 'bg-success-subtle text-success',
        'low' => 'bg-warning-subtle text-warning',
        default => 'bg-primary-subtle text-primary',
    };
@endphp

@if($gaHasAiSummary || $gaSummaryText !== '')
    <div class="ga-ai-insight-card mb-3">
        <div class="ga-ai-insight-header">
            <div class="d-flex align-items-start gap-3">
                <div class="ga-ai-insight-icon">
                    <i class="bi bi-stars"></i>
                </div>

                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <div class="ga-ai-insight-title">
                            {{ $gaHasAiSummary ? 'AI Google Analytics Insight' : 'Google Analytics Summary' }}
                        </div>

                        @if($gaHasAiSummary)
                            <span class="badge rounded-pill bg-success-subtle text-success">
                                AI Generated
                            </span>
                        @else
                            <span class="badge rounded-pill bg-light text-muted">
                                Local Summary
                            </span>
                        @endif

                        @if($gaHasAiSummary)
                            <span class="badge rounded-pill {{ $gaConfidenceBadgeClass }}">
                                Confidence: {{ ucfirst($gaAiConfidence) }}
                            </span>
                        @endif
                    </div>

                    <p class="ga-ai-insight-subtitle mb-0">
                        {{ $gaHasAiSummary
                            ? 'Ringkasan AI berdasarkan traffic, channel, landing page, funnel, content, device, dan location.'
                            : 'Ringkasan sementara dari rule lokal. Jalankan sync dengan --with-ai untuk insight AI lengkap.'
                        }}
                    </p>
                </div>
            </div>

            @if($gaHasAiSummary && ($gaAiModel || $gaAiGeneratedAt || $gaAiSource))
                <div class="ga-ai-insight-meta">
                    @if($gaAiModel)
                        <div>Model: <strong>{{ $gaAiModel }}</strong></div>
                    @endif

                    @if($gaAiGeneratedAt)
                        <div>Generated: <strong>{{ \Carbon\Carbon::parse($gaAiGeneratedAt)->format('d M Y H:i') }}</strong></div>
                    @endif

                    @if($gaAiSource)
                        <div>Source: <strong>{{ ucfirst($gaAiSource) }}</strong></div>
                    @endif
                </div>
            @endif
        </div>

        <div class="ga-ai-summary-box">
            <div class="fw-semibold text-dark mb-1">Executive Summary</div>
            <p class="text-muted mb-0">
                {{ $gaHasAiSummary ? $gaAiMainSummary : $gaSummaryText }}
            </p>
        </div>

        @if($gaHasAiSummary)
            <div class="row g-3 mt-1">
                <div class="col-lg-5">
                    <div class="ga-ai-panel h-100">
                        <div class="ga-ai-panel-label">
                            <i class="bi bi-exclamation-diamond-fill me-1"></i>
                            Main Bottleneck
                        </div>

                        <div class="ga-ai-bottleneck-text">
                            {{ $gaAiMainBottleneck ?: 'Belum ada bottleneck utama yang jelas dari data.' }}
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="ga-ai-panel h-100">
                        <div class="ga-ai-panel-label">
                            <i class="bi bi-bullseye me-1"></i>
                            Priority Focus
                        </div>

                        @if($gaAiPriorityFocus->isEmpty())
                            <p class="text-muted mb-0">Belum ada priority focus dari AI.</p>
                        @else
                            <div class="ga-ai-chip-list">
                                @foreach($gaAiPriorityFocus as $focus)
                                    <span class="ga-ai-chip">{{ $focus }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-lg-7">
                    <div class="ga-ai-panel h-100">
                        <div class="ga-ai-panel-label">
                            <i class="bi bi-list-check me-1"></i>
                            Recommended Actions
                        </div>

                        @if($gaAiRecommendedActions->isEmpty())
                            <p class="text-muted mb-0">Belum ada rekomendasi action dari AI.</p>
                        @else
                            <ol class="ga-ai-action-list mb-0">
                                @foreach($gaAiRecommendedActions as $action)
                                    <li>{{ $action }}</li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="ga-ai-panel h-100">
                        <div class="ga-ai-panel-label">
                            <i class="bi bi-shield-exclamation me-1"></i>
                            Risks to Watch
                        </div>

                        @if($gaAiRisks->isEmpty())
                            <p class="text-muted mb-0">Belum ada risk khusus dari AI.</p>
                        @else
                            <div class="ga-ai-risk-list">
                                @foreach($gaAiRisks as $risk)
                                    <div class="ga-ai-risk-item">
                                        <i class="bi bi-dot"></i>
                                        <span>{{ $risk }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="ga-ai-empty-hint mt-3">
                <i class="bi bi-terminal me-1"></i>
                Jalankan:
                <code>php artisan google-analytics:sync-dashboard --with-ai</code>
                untuk mengisi AI insight lengkap.
            </div>
        @endif
    </div>
@endif

@once
    @push('styles')
        <style>
            .ga-ai-insight-card {
                border: 1px solid rgba(91, 62, 142, 0.12);
                border-radius: 22px;
                background:
                    radial-gradient(circle at top left, rgba(91, 62, 142, 0.12), transparent 34%),
                    linear-gradient(135deg, rgba(91, 62, 142, 0.06), rgba(255, 190, 4, 0.08));
                padding: 1.15rem;
                box-shadow: 0 16px 36px rgba(15, 23, 42, 0.05);
            }

            .ga-ai-insight-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 1rem;
                flex-wrap: wrap;
                margin-bottom: 1rem;
            }

            .ga-ai-insight-icon {
                width: 46px;
                height: 46px;
                border-radius: 16px;
                background: rgba(91, 62, 142, 0.14);
                color: #5B3E8E;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 auto;
                font-size: 1.25rem;
                box-shadow: 0 10px 24px rgba(91, 62, 142, 0.10);
            }

            .ga-ai-insight-title {
                color: #111827;
                font-size: 1rem;
                font-weight: 900;
                line-height: 1.2;
            }

            .ga-ai-insight-subtitle {
                color: #64748b;
                font-size: .86rem;
                font-weight: 600;
                line-height: 1.45;
            }

            .ga-ai-insight-meta {
                color: #64748b;
                font-size: .78rem;
                line-height: 1.5;
                text-align: right;
                background: rgba(255, 255, 255, 0.66);
                border: 1px solid rgba(15, 23, 42, 0.06);
                border-radius: 14px;
                padding: .65rem .8rem;
            }

            .ga-ai-insight-meta strong {
                color: #111827;
            }

            .ga-ai-summary-box,
            .ga-ai-panel {
                background: rgba(255, 255, 255, 0.86);
                border: 1px solid rgba(15, 23, 42, 0.08);
                border-radius: 18px;
                padding: 1rem;
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
            }

            .ga-ai-panel-label {
                color: #5B3E8E;
                font-size: .76rem;
                font-weight: 900;
                letter-spacing: .04em;
                text-transform: uppercase;
                margin-bottom: .75rem;
            }

            .ga-ai-bottleneck-text {
                color: #111827;
                font-size: .96rem;
                font-weight: 800;
                line-height: 1.45;
            }

            .ga-ai-chip-list {
                display: flex;
                flex-wrap: wrap;
                gap: .55rem;
            }

            .ga-ai-chip {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                border: 1px solid rgba(91, 62, 142, 0.14);
                background: rgba(91, 62, 142, 0.08);
                color: #5B3E8E;
                font-size: .78rem;
                font-weight: 800;
                line-height: 1.25;
                padding: .45rem .7rem;
            }

            .ga-ai-action-list {
                color: #334155;
                padding-left: 1.15rem;
            }

            .ga-ai-action-list li {
                margin-bottom: .55rem;
                padding-left: .15rem;
                line-height: 1.45;
            }

            .ga-ai-action-list li:last-child {
                margin-bottom: 0;
            }

            .ga-ai-risk-list {
                display: grid;
                gap: .55rem;
            }

            .ga-ai-risk-item {
                display: flex;
                align-items: flex-start;
                gap: .35rem;
                color: #475569;
                font-size: .88rem;
                line-height: 1.4;
            }

            .ga-ai-risk-item i {
                color: #f59e0b;
                font-size: 1.1rem;
                line-height: 1.2;
            }

            .ga-ai-empty-hint {
                color: #64748b;
                font-size: .84rem;
                font-weight: 700;
                background: rgba(255, 255, 255, 0.72);
                border: 1px dashed rgba(91, 62, 142, 0.20);
                border-radius: 14px;
                padding: .8rem .9rem;
            }

            .ga-ai-empty-hint code {
                color: #5B3E8E;
                background: rgba(91, 62, 142, 0.08);
                border: 1px solid rgba(91, 62, 142, 0.12);
                border-radius: 8px;
                padding: .15rem .35rem;
            }

            @media (max-width: 767.98px) {
                .ga-ai-insight-meta {
                    width: 100%;
                    text-align: left;
                }
            }
        </style>
    @endpush
@endonce