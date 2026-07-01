<?php

namespace App\Services\GoogleAnalytics;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class GoogleAnalyticsAiInsightService
{
    public function generate(array $dashboardInsight): array
    {
        $this->ensureConfigured();

        $model = $this->model();
        $prompt = $this->buildPrompt($dashboardInsight);

        $response = Http::timeout(60)
            ->retry(2, 1200)
            ->asJson()
            ->post(
                'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $this->apiKey(),
                [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                [
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.35,
                        'responseMimeType' => 'application/json',
                    ],
                ]
            );

        $response->throw();

        $rawText = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');

        $decoded = $this->decodeJsonText($rawText);

        return $this->normalizeAiOutput($decoded, $model);
    }

    protected function buildPrompt(array $dashboardInsight): string
    {
        $context = $this->buildContext($dashboardInsight);

        return <<<PROMPT
You are a senior digital marketing analyst for FlexLabs.

Analyze this Google Analytics dashboard data and return ONLY valid JSON.
Use Indonesian language.
Be practical, owner-friendly, and decision-oriented.
Do not explain GA theory.
Focus on what the owner should do next.

JSON schema:
{
  "summary": "short executive summary, max 3 sentences",
  "main_bottleneck": "single biggest issue or opportunity",
  "priority_focus": [
    "focus item 1",
    "focus item 2",
    "focus item 3"
  ],
  "recommended_actions": [
    "action 1",
    "action 2",
    "action 3",
    "action 4"
  ],
  "risks": [
    "risk 1",
    "risk 2"
  ],
  "confidence": "low|medium|high"
}

Important:
- If engagement rate is low, explain why it matters.
- If source/channel data exists, mention which source/channel should be watched.
- If landing page data exists, mention landing page optimization.
- If custom conversion events are missing or weak, say tracking/conversion event needs improvement.
- Never invent exact numbers that are not in the data.

Google Analytics data:
{$context}
PROMPT;
    }

    protected function buildContext(array $dashboardInsight): string
    {
        $kpis = $dashboardInsight['kpis'] ?? [];
        $acquisition = $dashboardInsight['acquisition'] ?? [];

        $context = [
            'period' => $dashboardInsight['period'] ?? [],
            'kpis' => [
                'total_users' => $kpis['total_users'] ?? 0,
                'new_users' => $kpis['new_users'] ?? 0,
                'sessions' => $kpis['sessions'] ?? 0,
                'engaged_sessions' => $kpis['engaged_sessions'] ?? 0,
                'engagement_rate' => $kpis['engagement_rate'] ?? 0,
                'bounce_rate' => $kpis['bounce_rate'] ?? 0,
                'average_engagement_time_label' => $kpis['average_engagement_time_label'] ?? '0s',
                'key_events' => $kpis['key_events'] ?? 0,
                'key_event_rate' => $kpis['key_event_rate'] ?? 0,
            ],
            'top_channels' => collect($acquisition['channels'] ?? [])->take(5)->values()->all(),
            'top_sources' => collect($acquisition['sources'] ?? [])->take(5)->values()->all(),
            'top_campaigns' => collect($acquisition['campaigns'] ?? [])->take(5)->values()->all(),
            'top_landing_pages' => collect($dashboardInsight['landing_pages'] ?? [])->take(5)->values()->all(),
            'conversion_funnel' => collect($dashboardInsight['conversion_funnel'] ?? [])->values()->all(),
            'top_content_pages' => collect($dashboardInsight['content_pages'] ?? [])->take(5)->values()->all(),
            'devices' => collect($dashboardInsight['devices'] ?? [])->take(5)->values()->all(),
            'locations' => collect($dashboardInsight['locations'] ?? [])->take(5)->values()->all(),
        ];

        return json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function decodeJsonText(string $text): array
    {
        $text = trim($text);

        $text = preg_replace('/^```json\s*/i', '', $text);
        $text = preg_replace('/^```\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
        $text = trim((string) $text);

        $decoded = json_decode($text, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        $jsonStart = strpos($text, '{');
        $jsonEnd = strrpos($text, '}');

        if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
            $json = substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
            $decoded = json_decode($json, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        throw new RuntimeException('AI response is not valid JSON.');
    }

    protected function normalizeAiOutput(array $output, string $model): array
    {
        $summary = trim((string) ($output['summary'] ?? ''));

        if ($summary === '') {
            throw new RuntimeException('AI summary is empty.');
        }

        return [
            'summary' => $summary,
            'main_bottleneck' => trim((string) ($output['main_bottleneck'] ?? 'Belum ada bottleneck utama yang jelas dari data.')),
            'priority_focus' => collect($output['priority_focus'] ?? [])
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->take(5)
                ->values()
                ->all(),
            'recommended_actions' => collect($output['recommended_actions'] ?? [])
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->take(8)
                ->values()
                ->all(),
            'risks' => collect($output['risks'] ?? [])
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->take(5)
                ->values()
                ->all(),
            'confidence' => in_array(($output['confidence'] ?? 'medium'), ['low', 'medium', 'high'], true)
                ? $output['confidence']
                : 'medium',
            'source' => 'gemini',
            'model' => $model,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    protected function ensureConfigured(): void
    {
        if (blank($this->apiKey())) {
            throw new RuntimeException('Gemini API key is missing. Check GEMINI_API_KEY.');
        }

        if (blank($this->model())) {
            throw new RuntimeException('Google Analytics AI model is missing.');
        }
    }

    protected function apiKey(): ?string
    {
        return config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
    }

    protected function model(): string
    {
        return (string) (
            config('services.google_analytics.ai_model')
            ?: config('services.gemini.model')
            ?: env('GEMINI_MODEL', 'gemini-2.5-flash')
        );
    }
}