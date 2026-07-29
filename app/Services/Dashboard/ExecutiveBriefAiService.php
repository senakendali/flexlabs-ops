<?php

namespace App\Services\Dashboard;

use App\Services\Ai\GeminiClientService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExecutiveBriefAiService
{
    private const CACHE_VERSION = 'v1';

    private const FAILURE_CACHE_MINUTES = 10;

    public function __construct(
        private readonly GeminiClientService $geminiClientService
    ) {
    }

    /**
     * Generate one decision brief for the complete KPI scorecard. A successful
     * result is kept while the period and KPI fingerprint remain unchanged.
     */
    public function generate(
        array $scorecard,
        array $period,
        array $fallback
    ): array {
        $payload = $this->buildPayload($scorecard, $period);
        $fingerprint = hash('sha256', json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
        ));
        $cacheKey = sprintf(
            'executive-brief:%s:%s:%s',
            self::CACHE_VERSION,
            $period['month'],
            $fingerprint
        );

        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $brief = $this->normalize(
                $this->geminiClientService->generateJson(
                    $this->buildPrompt($payload)
                ),
                $fallback,
                $period,
                $fingerprint
            );

            Cache::forever($cacheKey, $brief);

            return $brief;
        } catch (Throwable $exception) {
            Log::warning('AI Executive Brief generation failed.', [
                'period' => $period['month'] ?? null,
                'fingerprint' => $fingerprint,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            $fallback = [
                ...$fallback,
                'generated_at' => now()->toIso8601String(),
                'generation_type' => 'local_fallback',
                'is_ai_generated' => false,
                'fingerprint' => $fingerprint,
            ];

            Cache::put(
                $cacheKey,
                $fallback,
                now()->addMinutes(self::FAILURE_CACHE_MINUTES)
            );

            return $fallback;
        }
    }

    private function buildPayload(array $scorecard, array $period): array
    {
        return [
            'period' => [
                'month' => $period['month'],
                'label' => $period['label'],
                'date_from' => $period['date_from'],
                'date_to' => $period['date_to'],
                'actual_date_to' => $period['actual_date_to'],
                'previous_month' => $period['previous_month'],
                'previous_label' => $period['previous_label'],
                'elapsed_percentage' => $period['elapsed_percentage'],
            ],
            'kpis' => collect($scorecard)
                ->map(function (array $kpi): array {
                    $status = $this->aiStatus((string) ($kpi['status'] ?? 'unavailable'));
                    $actual = $kpi['actual_available'] && $kpi['has_data']
                        ? (float) $kpi['actual_value']
                        : null;
                    $runningTarget = isset($kpi['expected_to_date'])
                        ? (float) $kpi['expected_to_date']
                        : null;

                    return [
                        'code' => $kpi['code'],
                        'name' => $kpi['name'],
                        'unit' => $kpi['unit'],
                        'direction' => $kpi['direction'],
                        'actual' => $actual,
                        'actual_formatted' => $kpi['actual_formatted'],
                        'period_target' => isset($kpi['target_value'])
                            ? (float) $kpi['target_value']
                            : null,
                        'running_target' => $runningTarget,
                        'achievement_percentage' => $kpi['achievement_percentage'],
                        'status' => $status,
                        'status_reason' => $kpi['status_reason'],
                        'gap_to_running_target' => $this->gap(
                            $actual,
                            $runningTarget,
                            (string) $kpi['direction']
                        ),
                        'previous_period' => ($kpi['trend']['available'] ?? false)
                            ? [
                                'actual' => (float) $kpi['trend']['previous_value'],
                                'change' => (float) $kpi['trend']['change_value'],
                                'change_percentage' => $kpi['trend']['change_percentage'],
                            ]
                            : null,
                        'source' => [
                            'name' => $kpi['source_label'],
                            'available' => (bool) $kpi['actual_available'],
                            'has_data' => (bool) $kpi['has_data'],
                            'message' => $kpi['source_message'],
                            'last_recorded_at' => $kpi['last_recorded_at'],
                        ],
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function buildPrompt(array $payload): string
    {
        $json = json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return <<<PROMPT
Anda adalah analis eksekutif FlexLabs. Buat satu AI Executive Brief berdasarkan seluruh data KPI berikut dalam satu analisis.

DATA KPI:
{$json}

Ketentuan wajib:
- Gunakan Bahasa Indonesia profesional, ringkas, dan berorientasi keputusan.
- Total isi sekitar 150-220 kata.
- Jangan mengarang penyebab atau fakta di luar data.
- Jika penyebab belum dapat dipastikan, gunakan frasa "indikasi awal" dan sebutkan data yang masih perlu diperiksa.
- Jangan menyebut KPI berstatus unavailable sebagai critical.
- Jangan membuat klaim root cause bila data pendukung tidak tersedia.
- Recommended actions harus spesifik, actionable, dan menyebut nama KPI yang ditangani.
- Prioritas harus tepat salah satu dari: Immediate, This Week, Monitor.
- Return JSON saja, tanpa markdown atau code fence.

Format JSON wajib:
{
  "headline": "Executive headline",
  "summary": "Ringkasan kondisi seluruh KPI",
  "possible_causes": ["Kemungkinan penyebab berbasis data atau indikasi awal"],
  "recommended_actions": ["Tindakan spesifik yang menyebut KPI"],
  "priority": "Immediate|This Week|Monitor"
}
PROMPT;
    }

    private function normalize(
        array $response,
        array $fallback,
        array $period,
        string $fingerprint
    ): array {
        $headline = trim((string) ($response['headline'] ?? ''));
        $summary = trim((string) ($response['summary'] ?? ''));
        $causes = $this->stringList($response['possible_causes'] ?? []);
        $actions = $this->stringList($response['recommended_actions'] ?? []);
        $priority = trim((string) ($response['priority'] ?? ''));

        if ($headline === '' || $summary === '' || $actions === []) {
            throw new \RuntimeException('AI Executive Brief response is incomplete.');
        }

        if (! in_array($priority, ['Immediate', 'This Week', 'Monitor'], true)) {
            $priority = $fallback['priority'];
        }

        return [
            'title' => 'Executive Brief — ' . $period['label'],
            'headline' => $headline,
            'summary' => $summary,
            'root_causes' => $causes,
            'recommendations' => $actions,
            'priority' => $priority,
            'generated_at' => now()->toIso8601String(),
            'generation_type' => 'ai',
            'is_ai_generated' => true,
            'fingerprint' => $fingerprint,
        ];
    }

    private function stringList(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn (mixed $item) => is_string($item) && trim($item) !== '')
            ->map(fn (string $item) => trim($item))
            ->values()
            ->all();
    }

    private function aiStatus(string $status): string
    {
        return match ($status) {
            'healthy' => 'healthy',
            'watch' => 'attention',
            'critical' => 'critical',
            default => 'unavailable',
        };
    }

    private function gap(?float $actual, ?float $target, string $direction): ?float
    {
        if ($actual === null || $target === null) {
            return null;
        }

        $gap = $direction === 'lower'
            ? $actual - $target
            : $target - $actual;

        return round($gap, 4);
    }
}
