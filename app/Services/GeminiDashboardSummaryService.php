<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GeminiDashboardSummaryService
{
    public function summarize(array $context, array $fallbackSummary): array
    {
        $fallbackSummary = $this->withFallbackMeta($fallbackSummary);

        if (! $this->isEnabled()) {
            if ($this->isDebugEnabled()) {
                $fallbackSummary['source'] = 'local_fallback';
                $fallbackSummary['debug'] = [
                    'enabled' => false,
                    'reason' => 'Gemini config belum lengkap. Cek GEMINI_API_KEY, GEMINI_MODEL, dan GEMINI_API_ENDPOINT.',
                    'model' => config('services.gemini.model'),
                    'endpoint' => config('services.gemini.endpoint'),
                    'has_api_key' => filled((string) config('services.gemini.api_key')),
                ];
            }

            return $fallbackSummary;
        }

        $payload = $this->buildDashboardPayload($context);
        $cacheKey = 'dashboard:gemini-summary:' . md5(json_encode($payload));
        $cacheMinutes = (int) config('services.gemini.cache_minutes', 15);

        $resolver = function () use ($payload, $fallbackSummary) {
            try {
                $summary = $this->requestSummary($payload, $fallbackSummary);

                return $this->normalizeSummary($summary, $fallbackSummary);
            } catch (Throwable $exception) {
                Log::warning('Gemini dashboard summary failed.', [
                    'message' => $exception->getMessage(),
                ]);

                $fallbackSummary['source'] = 'local_fallback';

                if ($this->isDebugEnabled()) {
                    $fallbackSummary['debug'] = [
                        'enabled' => true,
                        'reason' => 'Gemini request gagal, dashboard memakai local fallback.',
                        'error' => $exception->getMessage(),
                        'model' => config('services.gemini.model'),
                        'endpoint' => config('services.gemini.endpoint'),
                    ];
                }

                return $fallbackSummary;
            }
        };

        if ($this->shouldBypassCache()) {
            return $resolver();
        }

        return Cache::remember($cacheKey, now()->addMinutes(max(1, $cacheMinutes)), $resolver);
    }

    protected function isEnabled(): bool
    {
        return filled((string) config('services.gemini.api_key'))
            && filled((string) config('services.gemini.model'))
            && filled((string) config('services.gemini.endpoint'));
    }

    protected function isDebugEnabled(): bool
    {
        return filter_var(config('services.gemini.debug', false), FILTER_VALIDATE_BOOLEAN);
    }

    protected function shouldBypassCache(): bool
    {
        return $this->isDebugEnabled()
            || filter_var(config('services.gemini.bypass_cache', false), FILTER_VALIDATE_BOOLEAN);
    }

    protected function requestSummary(array $payload, array $fallbackSummary): array
    {
        $endpoint = rtrim((string) config('services.gemini.endpoint'), '/');
        $model = trim((string) config('services.gemini.model'));
        $apiKey = (string) config('services.gemini.api_key');
        $timeout = (int) config('services.gemini.timeout', 30);

        $url = $endpoint . '/models/' . rawurlencode($model) . ':generateContent';
        $prompt = $this->buildPrompt($payload, $fallbackSummary);
        $requestBody = [
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
                'temperature' => 0.25,
                'topP' => 0.9,
                'maxOutputTokens' => 900,
                'responseMimeType' => 'application/json',
            ],
        ];

        $response = Http::timeout(max(5, $timeout))
            ->withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $apiKey,
            ])
            ->post($url, $requestBody);

        $responseJson = $response->json();
        $responseText = (string) data_get($responseJson, 'candidates.0.content.parts.0.text', '');

        if ($this->isDebugEnabled()) {
            Log::info('Gemini dashboard raw response.', [
                'url' => $url,
                'model' => $model,
                'status' => $response->status(),
                'successful' => $response->successful(),
                'response_text' => Str::limit($responseText, 4000),
                'response_body' => Str::limit($response->body(), 8000),
                'dashboard_payload' => $payload,
            ]);
        }

        if ($response->failed()) {
            throw new \RuntimeException('Gemini API error: HTTP ' . $response->status() . ' - ' . Str::limit($response->body(), 800));
        }

        $decoded = $this->decodeJsonText($responseText);

        if (! is_array($decoded)) {
            throw new \RuntimeException('Gemini returned invalid JSON. Raw text: ' . Str::limit($responseText ?: $response->body(), 800));
        }

        if ($this->isDebugEnabled()) {
            $decoded['_debug'] = [
                'enabled' => true,
                'source' => 'gemini',
                'url' => $url,
                'model' => $model,
                'http_status' => $response->status(),
                'successful' => $response->successful(),
                'response_text' => Str::limit($responseText, 4000),
                'response_body' => Str::limit($response->body(), 8000),
                'payload_summary' => [
                    'period' => $payload['period'] ?? [],
                    'sales' => $payload['sales'] ?? [],
                    'finance' => $payload['finance'] ?? [],
                    'trial' => $payload['trial'] ?? [],
                    'workshop' => $payload['workshop'] ?? [],
                ],
            ];
        }

        return $decoded;
    }

    protected function buildDashboardPayload(array $context): array
    {
        $sales = $context['sales_insight'] ?? [];
        $finance = $context['finance_insight'] ?? [];
        $orders = $context['order_insight'] ?? [];
        $batch = $context['batch_capacity'] ?? [];
        $trialStats = $context['trial_stats'] ?? [];
        $trialStatus = $context['trial_status_counts'] ?? [];
        $workshop = $context['workshop_insight'] ?? [];
        $workshopStats = $context['workshop_stats'] ?? [];
        $workshopStatus = $context['workshop_status_counts'] ?? [];
        $upcomingBatches = $context['upcoming_batches'] ?? [];
        $upcomingWorkshopSchedules = $context['upcoming_workshop_schedules'] ?? [];

        return [
            'period' => [
                'label' => now()->format('F Y'),
                'date' => now()->toDateString(),
                'timezone' => config('app.timezone'),
            ],
            'sales' => [
                'leads_this_month' => (int) ($sales['leads_this_month'] ?? 0),
                'interacted_this_month' => (int) ($sales['interacted_this_month'] ?? 0),
                'consultation_this_month' => (int) ($sales['consultation_this_month'] ?? 0),
                'hot_leads_this_month' => (int) ($sales['hot_leads_this_month'] ?? 0),
                'closing_this_month' => (int) ($sales['closing_this_month'] ?? 0),
                'paid_this_month' => (int) ($sales['paid_this_month'] ?? 0),
                'interaction_rate' => (float) ($sales['interaction_rate'] ?? 0),
                'closing_rate' => (float) ($sales['closing_rate'] ?? 0),
                'paid_rate' => (float) ($sales['paid_rate'] ?? 0),
            ],
            'finance' => [
                'revenue_this_month' => (float) ($finance['revenue_this_month'] ?? 0),
                'revenue_last_month' => (float) ($finance['revenue_last_month'] ?? 0),
                'revenue_growth_percent' => (float) ($finance['revenue_growth_percent'] ?? 0),
                'pending_payment_count' => (int) ($finance['pending_payment_count'] ?? 0),
                'pending_payment_total' => (float) ($finance['pending_payment_total'] ?? 0),
                'overdue_schedule_count' => (int) ($finance['overdue_schedule_count'] ?? 0),
                'overdue_schedule_total' => (float) ($finance['overdue_schedule_total'] ?? 0),
                'last_payment_date' => $finance['last_payment_date'] ?? null,
                'days_since_last_payment' => $finance['days_since_last_payment'] ?? null,
            ],
            'orders' => [
                'orders_this_month' => (int) ($orders['orders_this_month'] ?? 0),
                'program_orders_this_month' => (int) ($orders['program_orders_this_month'] ?? 0),
                'workshop_orders_this_month' => (int) ($orders['workshop_orders_this_month'] ?? 0),
                'pending_orders' => (int) ($orders['pending_orders'] ?? 0),
                'partial_orders' => (int) ($orders['partial_orders'] ?? 0),
                'paid_orders' => (int) ($orders['paid_orders'] ?? 0),
                'potential_revenue' => (float) ($orders['potential_revenue'] ?? 0),
            ],
            'batch' => [
                'total_capacity' => (int) ($batch['total_capacity'] ?? 0),
                'filled_seats' => (int) ($batch['filled_seats'] ?? 0),
                'remaining_seats' => (int) ($batch['remaining_seats'] ?? 0),
                'utilization_percent' => (float) ($batch['utilization_percent'] ?? 0),
                'upcoming_batches_count' => is_countable($upcomingBatches) ? count($upcomingBatches) : 0,
            ],
            'trial' => [
                'participants_this_month' => (int) ($trialStats['participants_this_month'] ?? $trialStats['participants_new_this_month'] ?? 0),
                'participants_all_time' => (int) ($trialStats['participants_all_time'] ?? $trialStats['participants_total'] ?? 0),
                'schedules_this_month' => (int) ($trialStats['schedules_this_month'] ?? 0),
                'schedules_active_this_month' => (int) ($trialStats['schedules_active_this_month'] ?? $trialStats['schedules_active'] ?? 0),
                'follow_up_progress' => (int) ($context['trial_follow_up_progress'] ?? 0),
                'status_counts' => [
                    'registered' => (int) data_get($trialStatus, 'registered', 0),
                    'contacted' => (int) data_get($trialStatus, 'contacted', 0),
                    'confirmed' => (int) data_get($trialStatus, 'confirmed', 0),
                    'attended' => (int) data_get($trialStatus, 'attended', 0),
                    'cancelled' => (int) data_get($trialStatus, 'cancelled', 0),
                    'no_show' => (int) data_get($trialStatus, 'no_show', 0),
                ],
            ],
            'workshop' => [
                'active_workshops' => (int) ($workshopStats['workshops_active'] ?? 0),
                'schedules_this_month' => (int) ($workshopStats['schedules_this_month'] ?? 0),
                'schedules_active_this_month' => (int) ($workshopStats['schedules_active_this_month'] ?? 0),
                'upcoming_schedules_count' => is_countable($upcomingWorkshopSchedules) ? count($upcomingWorkshopSchedules) : 0,
                'participants_this_month' => (int) ($workshop['participants_this_month'] ?? $workshopStats['participants_this_month'] ?? 0),
                'participants_all_time' => (int) ($workshopStats['participants_all_time'] ?? 0),
                'pending_payment' => (int) ($workshop['pending_payment'] ?? data_get($workshopStatus, 'pending_payment', 0)),
                'confirmed' => (int) ($workshop['confirmed'] ?? data_get($workshopStatus, 'confirmed', 0)),
                'attended' => (int) ($workshop['attended'] ?? data_get($workshopStatus, 'attended', 0)),
                'cancelled' => (int) ($workshop['cancelled'] ?? data_get($workshopStatus, 'cancelled', 0)),
                'paid_count_this_month' => (int) ($workshopStats['paid_count_this_month'] ?? 0),
                'revenue_this_month' => (float) ($workshopStats['revenue_this_month'] ?? $workshop['revenue_this_month'] ?? 0),
                'conversion_percent' => (float) ($workshopStats['conversion_percent'] ?? 0),
                'attendance_percent' => (float) ($workshopStats['attendance_percent'] ?? 0),
                'top_source' => $workshopStats['top_source'] ?? null,
                'top_source_total' => (int) ($workshopStats['top_source_total'] ?? 0),
            ],
        ];
    }

    protected function buildPrompt(array $payload, array $fallbackSummary): string
    {
        return trim(<<<PROMPT
You are an executive dashboard analyst for FlexLabs. Create a concise Indonesian management dashboard summary from the aggregate metrics below.

Rules:
- Use Bahasa Indonesia, professional but simple.
- Do not invent facts outside the metrics.
- Mention risk/action only when supported by the data.
- Be concise: headline max 80 characters, summary_text max 450 characters.
- Prefer practical business/action language for management.
- Return JSON only. No markdown, no code fence.
- Each item type must be one of: critical, warning, action, good, info.
- focus must contain the top 3 item objects, not strings.

Expected JSON shape:
{
  "headline": "short headline",
  "summary_text": "short management summary",
  "items": [
    {"type": "critical|warning|action|good|info", "title": "short title", "message": "short explanation"}
  ],
  "focus": [
    {"type": "critical|warning|action|good|info", "title": "short title", "message": "short explanation"}
  ]
}

Dashboard metrics:
{$this->prettyJson($payload)}

Local fallback summary for reference only. Improve wording if the metrics support it, but do not contradict the dashboard metrics:
{$this->prettyJson($fallbackSummary)}
PROMPT);
    }

    protected function normalizeSummary(array $summary, array $fallbackSummary): array
    {
        $items = collect($summary['items'] ?? [])
            ->map(fn ($item) => $this->normalizeItem($item))
            ->filter(fn ($item) => filled($item['title']) && filled($item['message']))
            ->values()
            ->take(6)
            ->all();

        if (empty($items)) {
            $items = collect($fallbackSummary['items'] ?? [])
                ->map(fn ($item) => $this->normalizeItem($item))
                ->filter(fn ($item) => filled($item['title']) && filled($item['message']))
                ->values()
                ->take(6)
                ->all();
        }

        $focus = collect($summary['focus'] ?? [])
            ->map(fn ($item) => is_array($item) ? $this->normalizeItem($item) : null)
            ->filter(fn ($item) => is_array($item) && filled($item['title']) && filled($item['message']))
            ->values()
            ->take(3)
            ->all();

        if (empty($focus)) {
            $focus = array_slice($items, 0, 3);
        }

        $headline = trim((string) ($summary['headline'] ?? ''));
        $summaryText = trim((string) ($summary['summary_text'] ?? $summary['summary'] ?? ''));

        $normalized = [
            'generated_at' => now()->format('d M Y H:i'),
            'source' => 'gemini',
            'headline' => filled($headline)
                ? Str::limit($headline, 90, '')
                : ($fallbackSummary['headline'] ?? 'Management Summary'),
            'summary_text' => filled($summaryText)
                ? Str::limit($summaryText, 600)
                : ($fallbackSummary['summary_text'] ?? ''),
            'items' => $items,
            'focus' => $focus,
        ];

        if ($this->isDebugEnabled() && isset($summary['_debug']) && is_array($summary['_debug'])) {
            $normalized['debug'] = $summary['_debug'];
        }

        return $normalized;
    }

    protected function normalizeItem(mixed $item): array
    {
        $item = is_array($item) ? $item : [];
        $type = (string) ($item['type'] ?? 'info');

        if (! in_array($type, ['critical', 'warning', 'action', 'good', 'info'], true)) {
            $type = 'info';
        }

        return [
            'type' => $type,
            'title' => Str::limit(trim((string) ($item['title'] ?? 'Insight')), 80, ''),
            'message' => Str::limit(trim((string) ($item['message'] ?? $item['description'] ?? $item['text'] ?? '')), 220),
        ];
    }

    protected function decodeJsonText(string $text): ?array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?/i', '', $text) ?? $text;
        $text = preg_replace('/```$/', '', trim($text)) ?? $text;
        $text = trim($text);

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    protected function withFallbackMeta(array $summary): array
    {
        $summary['source'] = $summary['source'] ?? 'local';
        $summary['generated_at'] = $summary['generated_at'] ?? now()->format('d M Y H:i');

        return $summary;
    }

    protected function prettyJson(array $payload): string
    {
        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}
