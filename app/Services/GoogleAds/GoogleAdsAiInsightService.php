<?php

namespace App\Services\GoogleAds;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleAdsAiInsightService
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
                                ['text' => $prompt],
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
You are a senior Google Ads performance marketer for FlexLabs.

Analyze this Google Ads dashboard data and return ONLY valid JSON.
Use Indonesian language.
Be practical, owner-friendly, and decision-oriented.
Do not explain Google Ads theory.
Focus on budget efficiency, wasted spend, CTR, CPC, conversion bottlenecks, and next actions.

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
  "budget_notes": [
    "budget note 1",
    "budget note 2"
  ],
  "risks": [
    "risk 1",
    "risk 2"
  ],
  "confidence": "low|medium|high"
}

Important:
- If spend exists but conversions are low or zero, highlight tracking, landing page, keyword intent, and CTA issues.
- If CTR is low, mention ad copy, keyword relevance, and creative/asset quality.
- If a campaign converts, explain whether it should be scaled carefully.
- Never invent exact numbers that are not in the data.

Google Ads data:
{$context}
PROMPT;
    }

    protected function buildContext(array $dashboardInsight): string
    {
        $overview = $dashboardInsight['overview'] ?? [];

        $context = [
            'period' => $dashboardInsight['period'] ?? [],
            'overview' => [
                'campaign_count' => $overview['campaign_count'] ?? 0,
                'enabled_campaign_count' => $overview['enabled_campaign_count'] ?? 0,
                'paused_campaign_count' => $overview['paused_campaign_count'] ?? 0,
                'total_cost_label' => $overview['total_cost_label'] ?? 'Rp 0',
                'total_impressions' => $overview['total_impressions'] ?? 0,
                'total_clicks' => $overview['total_clicks'] ?? 0,
                'ctr' => $overview['ctr'] ?? 0,
                'average_cpc_label' => $overview['average_cpc_label'] ?? 'Rp 0',
                'total_conversions' => $overview['total_conversions'] ?? 0,
                'cost_per_conversion_label' => $overview['cost_per_conversion_label'] ?? '-',
                'conversion_rate' => $overview['conversion_rate'] ?? 0,
                'roas' => $overview['roas'] ?? 0,
                'critical_count' => $overview['critical_count'] ?? 0,
                'attention_count' => $overview['attention_count'] ?? 0,
                'healthy_count' => $overview['healthy_count'] ?? 0,
                'best_campaign' => $overview['best_campaign'] ?? null,
            ],
            'campaigns' => collect($dashboardInsight['campaigns'] ?? [])
                ->take(10)
                ->values()
                ->all(),
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

        throw new RuntimeException('Google Ads AI response is not valid JSON.');
    }

    protected function normalizeAiOutput(array $output, string $model): array
    {
        $summary = trim((string) ($output['summary'] ?? ''));

        if ($summary === '') {
            throw new RuntimeException('Google Ads AI summary is empty.');
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
            'budget_notes' => collect($output['budget_notes'] ?? [])
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->take(5)
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
            throw new RuntimeException('Google Ads AI model is missing.');
        }
    }

    protected function apiKey(): ?string
    {
        return config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
    }

    protected function model(): string
    {
        return (string) (
            config('services.google_ads.ai_model')
            ?: config('services.gemini.model')
            ?: env('GEMINI_MODEL', 'gemini-2.5-flash-lite')
        );
    }
}