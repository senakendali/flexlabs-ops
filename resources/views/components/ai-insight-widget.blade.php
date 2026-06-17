@props([
    'insight' => [],
    'title' => 'AI Insight',
    'headline' => null,
    'summary' => null,
    'items' => [],
    'focus' => [],
    'source' => null,
    'image' => null,
    'defaultOpen' => true,
])

@php
    $normalize = function ($value) {
        if ($value instanceof \Illuminate\Support\Collection) {
            return $value->toArray();
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        return is_array($value) ? $value : [];
    };

    $insightData = $normalize($insight);
    $itemsData = $normalize($items);
    $focusData = $normalize($focus);

    $widgetTitle = (string) ($title ?: 'AI Insight');
    $widgetHeadline = (string) ($headline ?? ($insightData['headline'] ?? 'Insight Summary'));
    $widgetSummary = trim((string) ($summary ?? ($insightData['summary_text'] ?? 'Insight belum tersedia untuk halaman ini.')));
    $widgetSummaryParagraphs = collect(preg_split('/\R{2,}/', $widgetSummary) ?: [])
        ->map(fn ($paragraph) => trim((string) $paragraph))
        ->filter()
        ->values();
    $sourceLabel = trim((string) ($source ?? ($insightData['source_label'] ?? 'Smart Local Insight')));
    $generatedAt = trim((string) ($insightData['generated_at'] ?? ''));

    $displayItems = collect($focusData)
        ->whenEmpty(fn () => collect($insightData['focus'] ?? []))
        ->whenEmpty(fn () => collect($itemsData))
        ->whenEmpty(fn () => collect($insightData['items'] ?? []))
        ->take(3)
        ->values();

    $robotImage = $image ?: asset('images/ai.png');

    $levelClass = function ($type) {
        return match ($type) {
            'critical' => 'is-critical',
            'warning', 'action' => 'is-warning',
            'good', 'success' => 'is-good',
            default => 'is-info',
        };
    };
@endphp

<div
    class="ai-insight-widget {{ $defaultOpen ? '' : 'is-collapsed' }}"
    data-ai-insight-widget
>
    <div class="ai-insight-bubble" data-ai-insight-bubble>
        <button
            type="button"
            class="ai-insight-close"
            data-ai-insight-close
            aria-label="Close AI insight"
        >
            <i class="bi bi-x"></i>
        </button>

        <div class="ai-insight-label">
            <i class="bi bi-stars"></i>
            {{ $widgetTitle }}
        </div>

        @if($widgetHeadline)
            <div class="ai-insight-headline">
                {{ $widgetHeadline }}
            </div>
        @endif

        @if($widgetSummaryParagraphs->isNotEmpty())
            <div class="ai-insight-text">
                @foreach($widgetSummaryParagraphs as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>
        @endif

        @if($displayItems->isNotEmpty())
            <div class="ai-insight-focus-list">
                @foreach($displayItems as $item)
                    @php
                        $item = is_string($item) ? ['title' => $item] : $normalize($item);
                        $itemType = (string) ($item['type'] ?? $item['level'] ?? 'info');
                        $itemTitle = trim((string) ($item['title'] ?? 'Insight'));
                        $itemMessage = trim((string) ($item['message'] ?? $item['description'] ?? ''));
                    @endphp

                    <div class="ai-insight-focus-item {{ $levelClass($itemType) }}">
                        <div class="ai-insight-focus-dot"></div>
                        <div class="ai-insight-focus-content">
                            <strong>{{ $itemTitle }}</strong>
                            @if($itemMessage)
                                <span>{{ $itemMessage }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($sourceLabel || $generatedAt)
            <div class="ai-insight-meta">
                @if($sourceLabel)
                    <span>{{ $sourceLabel }}</span>
                @endif

                @if($generatedAt)
                    <span>{{ $generatedAt }}</span>
                @endif
            </div>
        @endif
    </div>

    <button
        type="button"
        class="ai-insight-robot"
        data-ai-insight-toggle
        aria-label="Toggle AI insight"
    >
        <img src="{{ $robotImage }}" alt="AI Assistant">
    </button>
</div>

@once
    @push('styles')
        <style>
            .ai-insight-widget {
                position: fixed;
                right: 24px;
                bottom: 24px;
                z-index: 1050;
                display: flex;
                align-items: flex-end;
                gap: 12px;
                pointer-events: none;
            }

            .ai-insight-widget > * {
                pointer-events: auto;
            }

            .ai-insight-robot {
                width: 82px;
                height: 82px;
                border: 0;
                border-radius: 24px;
                background: linear-gradient(135deg, #5B3E8E 0%, #7C5AC7 100%);
                box-shadow: 0 18px 40px rgba(91, 62, 142, 0.30);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 10px;
                cursor: pointer;
                transition: transform 0.25s ease, box-shadow 0.25s ease;
                animation: aiInsightRiseUp 0.85s cubic-bezier(0.22, 1, 0.36, 1) both, aiInsightFloat 4s ease-in-out 1.1s infinite;
                transform-origin: bottom center;
            }

            .ai-insight-robot:hover {
                transform: translateY(-4px) scale(1.03);
                box-shadow: 0 24px 50px rgba(91, 62, 142, 0.38);
            }

            .ai-insight-robot img {
                width: 100%;
                height: 100%;
                object-fit: contain;
                display: block;
            }

            .ai-insight-bubble {
                width: min(420px, calc(100vw - 142px));
                max-height: min(68vh, 620px);
                overflow-y: auto;
                background: #ffffff;
                border: 1px solid rgba(91, 62, 142, 0.14);
                border-radius: 24px 24px 8px 24px;
                padding: 16px 18px 14px;
                box-shadow: 0 18px 48px rgba(15, 23, 42, 0.16);
                position: relative;
                animation: aiInsightBubbleIn 0.65s ease 0.18s both;
            }

            .ai-insight-bubble::after {
                content: '';
                position: absolute;
                right: -8px;
                bottom: 18px;
                width: 16px;
                height: 16px;
                background: #ffffff;
                border-right: 1px solid rgba(91, 62, 142, 0.14);
                border-bottom: 1px solid rgba(91, 62, 142, 0.14);
                transform: rotate(-45deg);
            }

            .ai-insight-label {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                font-size: 12px;
                font-weight: 800;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                color: #5B3E8E;
                margin-bottom: 8px;
                padding-right: 32px;
            }

            .ai-insight-headline {
                font-size: 15px;
                font-weight: 800;
                color: #111827;
                line-height: 1.35;
                margin-bottom: 7px;
                padding-right: 26px;
            }

            .ai-insight-text {
                font-size: 14px;
                line-height: 1.62;
                color: #374151;
            }

            .ai-insight-text p {
                margin: 0;
                white-space: pre-line;
            }

            .ai-insight-text p + p {
                margin-top: 10px;
                padding-top: 10px;
                border-top: 1px dashed rgba(91, 62, 142, 0.14);
            }

            .ai-insight-focus-list {
                display: grid;
                gap: 8px;
                margin-top: 12px;
            }

            .ai-insight-focus-item {
                display: grid;
                grid-template-columns: 9px 1fr;
                gap: 9px;
                padding: 10px 11px;
                border-radius: 14px;
                background: #f9fafb;
                border: 1px solid #eef2f7;
            }

            .ai-insight-focus-dot {
                width: 9px;
                height: 9px;
                border-radius: 999px;
                margin-top: 5px;
                background: #64748b;
            }

            .ai-insight-focus-item.is-critical {
                background: #fff7ed;
                border-color: #fed7aa;
            }

            .ai-insight-focus-item.is-critical .ai-insight-focus-dot {
                background: #f97316;
            }

            .ai-insight-focus-item.is-warning {
                background: #fffbeb;
                border-color: #fde68a;
            }

            .ai-insight-focus-item.is-warning .ai-insight-focus-dot {
                background: #f59e0b;
            }

            .ai-insight-focus-item.is-good {
                background: #f0fdf4;
                border-color: #bbf7d0;
            }

            .ai-insight-focus-item.is-good .ai-insight-focus-dot {
                background: #22c55e;
            }

            .ai-insight-focus-item.is-info {
                background: #f5f3ff;
                border-color: #ddd6fe;
            }

            .ai-insight-focus-item.is-info .ai-insight-focus-dot {
                background: #7c3aed;
            }

            .ai-insight-focus-content strong {
                display: block;
                font-size: 12.5px;
                line-height: 1.35;
                color: #111827;
                margin-bottom: 3px;
            }

            .ai-insight-focus-content span {
                display: block;
                font-size: 11.8px;
                line-height: 1.45;
                color: #4b5563;
            }

            .ai-insight-meta {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
                margin-top: 12px;
                padding-top: 10px;
                border-top: 1px solid #f1f5f9;
                font-size: 11px;
                font-weight: 700;
                color: #8b5cf6;
            }

            .ai-insight-meta span {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                background: #f5f3ff;
                padding: 4px 8px;
            }

            .ai-insight-close {
                position: absolute;
                top: 10px;
                right: 10px;
                width: 26px;
                height: 26px;
                border: 0;
                border-radius: 999px;
                background: #f3f4f6;
                color: #6b7280;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: background 0.2s ease, color 0.2s ease;
                z-index: 2;
            }

            .ai-insight-close:hover {
                background: #ede9fe;
                color: #5B3E8E;
            }

            .ai-insight-widget.is-collapsed .ai-insight-bubble {
                display: none;
            }

            @keyframes aiInsightRiseUp {
                0% {
                    opacity: 0;
                    transform: translateY(90px) scale(0.85);
                }
                55% {
                    opacity: 1;
                    transform: translateY(-8px) scale(1.02);
                }
                100% {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }

            @keyframes aiInsightBubbleIn {
                0% {
                    opacity: 0;
                    transform: translateY(16px) scale(0.96);
                }
                100% {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }

            @keyframes aiInsightFloat {
                0%, 100% {
                    translate: 0 0;
                }
                50% {
                    translate: 0 -5px;
                }
            }

            @media (max-width: 768px) {
                .ai-insight-widget {
                    right: 16px;
                    bottom: 16px;
                    gap: 8px;
                }

                .ai-insight-robot {
                    width: 68px;
                    height: 68px;
                    border-radius: 20px;
                }

                .ai-insight-bubble {
                    width: calc(100vw - 108px);
                    max-width: 320px;
                    padding: 14px 15px;
                }

                .ai-insight-text {
                    font-size: 13px;
                    line-height: 1.55;
                }
            }

            @media (max-width: 576px) {
                .ai-insight-bubble {
                    width: calc(100vw - 104px);
                    max-width: 286px;
                    max-height: 62vh;
                }

                .ai-insight-focus-content span {
                    font-size: 11.5px;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-ai-insight-widget]').forEach(function (widget) {
                    const toggle = widget.querySelector('[data-ai-insight-toggle]');
                    const close = widget.querySelector('[data-ai-insight-close]');

                    if (toggle) {
                        toggle.addEventListener('click', function () {
                            widget.classList.toggle('is-collapsed');
                        });
                    }

                    if (close) {
                        close.addEventListener('click', function (event) {
                            event.stopPropagation();
                            widget.classList.add('is-collapsed');
                        });
                    }
                });
            });
        </script>
    @endpush
@endonce
