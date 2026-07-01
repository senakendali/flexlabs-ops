@php
    $googleAdsAiSummary = is_array($googleAdsAiSummary ?? null) ? $googleAdsAiSummary : [];
    $googleAdsSummaryText = trim((string) ($googleAdsSummaryText ?? ''));

    $summary = trim((string) ($googleAdsAiSummary['summary'] ?? ''));
    $mainBottleneck = trim((string) ($googleAdsAiSummary['main_bottleneck'] ?? ''));

    $priorityFocus = collect($googleAdsAiSummary['priority_focus'] ?? [])->filter()->values();
    $recommendedActions = collect($googleAdsAiSummary['recommended_actions'] ?? [])->filter()->values();
    $budgetNotes = collect($googleAdsAiSummary['budget_notes'] ?? [])->filter()->values();
    $risks = collect($googleAdsAiSummary['risks'] ?? [])->filter()->values();

    $confidence = strtolower((string) ($googleAdsAiSummary['confidence'] ?? 'medium'));
    $model = $googleAdsAiSummary['model'] ?? null;
    $generatedAt = $googleAdsAiSummary['generated_at'] ?? null;

    $hasAi = $summary !== '';

    $confidenceClass = match ($confidence) {
        'high' => 'bg-success-subtle text-success',
        'low' => 'bg-warning-subtle text-warning',
        default => 'bg-primary-subtle text-primary',
    };
@endphp

@if($hasAi || $googleAdsSummaryText !== '')
    <div class="google-ads-ai-card mb-3">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
            <div class="d-flex align-items-start gap-3">
                <div class="google-ads-ai-icon">
                    <i class="bi bi-stars"></i>
                </div>
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <div class="google-ads-ai-title">
                            {{ $hasAi ? 'AI Google Ads Insight' : 'Google Ads Summary' }}
                        </div>

                        @if($hasAi)
                            <span class="badge rounded-pill bg-success-subtle text-success">AI Generated</span>
                            <span class="badge rounded-pill {{ $confidenceClass }}">
                                Confidence: {{ ucfirst($confidence) }}
                            </span>
                        @else
                            <span class="badge rounded-pill bg-light text-muted">Local Summary</span>
                        @endif
                    </div>

                    <p class="text-muted mb-0 small fw-semibold">
                        Insight untuk spend, CTR, CPC, conversion bottleneck, campaign health, dan budget action.
                    </p>
                </div>
            </div>

            @if($hasAi && ($model || $generatedAt))
                <div class="text-end small text-muted">
                    @if($model)
                        <div>Model: <strong class="text-dark">{{ $model }}</strong></div>
                    @endif
                    @if($generatedAt)
                        <div>Generated: <strong class="text-dark">{{ \Carbon\Carbon::parse($generatedAt)->format('d M Y H:i') }}</strong></div>
                    @endif
                </div>
            @endif
        </div>

        <div class="google-ads-ai-box mb-3">
            <div class="fw-semibold text-dark mb-1">Executive Summary</div>
            <p class="text-muted mb-0">{{ $hasAi ? $summary : $googleAdsSummaryText }}</p>
        </div>

        @if($hasAi)
            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="google-ads-ai-box h-100">
                        <div class="google-ads-ai-label">Main Bottleneck</div>
                        <div class="fw-bold text-dark">
                            {{ $mainBottleneck ?: 'Belum ada bottleneck utama yang jelas dari data.' }}
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="google-ads-ai-box h-100">
                        <div class="google-ads-ai-label">Priority Focus</div>

                        @if($priorityFocus->isEmpty())
                            <p class="text-muted mb-0">Belum ada priority focus dari AI.</p>
                        @else
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($priorityFocus as $focus)
                                    <span class="google-ads-ai-chip">{{ $focus }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="google-ads-ai-box h-100">
                        <div class="google-ads-ai-label">Recommended Actions</div>

                        @if($recommendedActions->isEmpty())
                            <p class="text-muted mb-0">Belum ada recommended action dari AI.</p>
                        @else
                            <ol class="mb-0 ps-3">
                                @foreach($recommendedActions as $action)
                                    <li class="mb-2">{{ $action }}</li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="google-ads-ai-box h-100">
                        <div class="google-ads-ai-label">Budget Notes & Risks</div>

                        @foreach($budgetNotes as $note)
                            <div class="small text-muted mb-2">
                                <i class="bi bi-cash-coin me-1 text-success"></i>{{ $note }}
                            </div>
                        @endforeach

                        @foreach($risks as $risk)
                            <div class="small text-muted mb-2">
                                <i class="bi bi-exclamation-triangle me-1 text-warning"></i>{{ $risk }}
                            </div>
                        @endforeach

                        @if($budgetNotes->isEmpty() && $risks->isEmpty())
                            <p class="text-muted mb-0">Belum ada budget note atau risk khusus.</p>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="google-ads-ai-hint mt-3">
                <i class="bi bi-terminal me-1"></i>
                Jalankan:
                <code>php artisan google-ads:sync-dashboard --with-ai</code>
                untuk mengisi AI insight lengkap.
            </div>
        @endif
    </div>
@endif

@once
    @push('styles')
        <style>
            .google-ads-ai-card {
                border: 1px solid rgba(66, 133, 244, 0.16);
                border-radius: 22px;
                background:
                    radial-gradient(circle at top left, rgba(66, 133, 244, 0.12), transparent 34%),
                    linear-gradient(135deg, rgba(66, 133, 244, 0.06), rgba(52, 168, 83, 0.08));
                padding: 1.15rem;
                box-shadow: 0 16px 36px rgba(15, 23, 42, 0.05);
            }

            .google-ads-ai-icon {
                width: 46px;
                height: 46px;
                border-radius: 16px;
                background: rgba(66, 133, 244, 0.14);
                color: #1a73e8;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 1.25rem;
                box-shadow: 0 10px 24px rgba(66, 133, 244, 0.10);
            }

            .google-ads-ai-title {
                color: #111827;
                font-size: 1rem;
                font-weight: 900;
                line-height: 1.2;
            }

            .google-ads-ai-box {
                background: rgba(255, 255, 255, 0.88);
                border: 1px solid rgba(15, 23, 42, 0.08);
                border-radius: 18px;
                padding: 1rem;
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
            }

            .google-ads-ai-label {
                color: #1a73e8;
                font-size: .76rem;
                font-weight: 900;
                letter-spacing: .04em;
                text-transform: uppercase;
                margin-bottom: .75rem;
            }

            .google-ads-ai-chip {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                border: 1px solid rgba(66, 133, 244, 0.14);
                background: rgba(66, 133, 244, 0.08);
                color: #1a73e8;
                font-size: .78rem;
                font-weight: 800;
                line-height: 1.25;
                padding: .45rem .7rem;
            }

            .google-ads-ai-hint {
                color: #64748b;
                font-size: .84rem;
                font-weight: 700;
                background: rgba(255, 255, 255, 0.72);
                border: 1px dashed rgba(66, 133, 244, 0.20);
                border-radius: 14px;
                padding: .8rem .9rem;
            }

            .google-ads-ai-hint code {
                color: #1a73e8;
                background: rgba(66, 133, 244, 0.08);
                border: 1px solid rgba(66, 133, 244, 0.12);
                border-radius: 8px;
                padding: .15rem .35rem;
            }
        </style>
    @endpush
@endonce