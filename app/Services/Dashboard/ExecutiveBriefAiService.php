<?php

namespace App\Services\Dashboard;

use App\Services\Ai\GeminiClientService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExecutiveBriefAiService
{
    private const CACHE_VERSION = 'v2';

    private const FAILURE_CACHE_MINUTES = 10;

    public function __construct(
        private readonly GeminiClientService $geminiClientService
    ) {}

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

        $fallback = $this->structuredFallback($fallback, $payload, $period, $fingerprint);
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
                        'academic_on_track_details' => $kpi['code'] === 'student_completion_rate'
                            ? [
                                'on_track_students' => data_get($kpi, 'actual_meta.on_track_students'),
                                'eligible_students' => data_get($kpi, 'actual_meta.eligible_students'),
                                'average_actual_progress' => data_get($kpi, 'actual_meta.average_actual_progress'),
                                'average_expected_progress' => data_get($kpi, 'actual_meta.average_expected_progress'),
                                'excluded_timeline_count' => data_get($kpi, 'actual_meta.excluded_timeline_count'),
                                'excluded_progress_count' => data_get($kpi, 'actual_meta.excluded_progress_count'),
                                'unavailable_reason' => $status === 'unavailable'
                                    ? $kpi['source_message']
                                    : null,
                            ]
                            : null,
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
Anda adalah analis eksekutif FlexLabs. Buat satu brief manajemen terstruktur berdasarkan seluruh data KPI berikut dalam SATU analisis.

DATA KPI:
{$json}

Ketentuan wajib:
- Gunakan Bahasa Indonesia profesional, ringkas, dan berorientasi keputusan.
- Jangan mengarang penyebab atau fakta di luar data.
- Jika sebab belum terbukti, finding_type wajib "indication" dan jelaskan data yang perlu diperiksa.
- Jangan menyebut KPI berstatus unavailable sebagai critical.
- Jangan membuat angka, proyeksi, nominal, conversion, SLA, lead idle, atau invoice overdue yang tidak ada pada payload.
- Root cause maksimum 4, risk/opportunity maksimum 3, recommended decisions 3-5.
- PIC hanya role: Sales Lead, Finance Lead, Marketing Lead, Academic Lead, atau Management.
- timeframe: Today, Within 48 Hours, This Week, atau Monitor.
- Return JSON saja, tanpa markdown atau code fence.

Format JSON wajib:
{
  "headline": "Executive headline",
  "executive_summary": "Ringkasan kondisi KPI dalam 2-4 kalimat",
  "root_causes": [{"rank":1,"title":"","evidence":"","source":"","severity":"critical|attention|unavailable","finding_type":"confirmed|indication"}],
  "risk_opportunity": [{"type":"risk|opportunity|watch","title":"","description":"","evidence":"","related_kpi":"","urgency":"high|medium|low"}],
  "recommended_decisions": [{"priority":"P1|P2|P3","action":"","pic_role":"","timeframe":"Today|Within 48 Hours|This Week|Monitor","related_kpi":"","expected_impact":"","reason":""}]
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
        $summary = trim((string) ($response['executive_summary'] ?? ''));
        $causes = $this->normalizeRootCauses($response['root_causes'] ?? []);
        $risks = $this->normalizeRisks($response['risk_opportunity'] ?? []);
        $decisions = $this->normalizeDecisions($response['recommended_decisions'] ?? []);

        if ($headline === '' || $summary === '' || $decisions === []) {
            throw new \RuntimeException('AI Executive Brief response is incomplete.');
        }

        return [
            'title' => 'Executive Brief — '.$period['label'],
            'headline' => $headline,
            'executive_summary' => $summary,
            'summary' => $summary,
            'root_causes' => $causes,
            'risk_opportunity' => $risks,
            'recommended_decisions' => $decisions,
            'recommendations' => collect($decisions)->pluck('action')->all(),
            'priority' => $decisions[0]['timeframe'] ?? 'Monitor',
            'confidence' => $fallback['confidence'],
            'generated_at' => now()->toIso8601String(),
            'generation_type' => 'ai',
            'is_ai_generated' => true,
            'provider' => 'Gemini',
            'fingerprint' => $fingerprint,
        ];
    }

    private function structuredFallback(array $fallback, array $payload, array $period, string $fingerprint): array
    {
        $issues = collect($payload['kpis'])->whereIn('status', ['critical', 'attention']);
        $causes = $issues->take(4)->values()->map(fn (array $kpi, int $index) => [
            'rank' => $index + 1,
            'title' => $kpi['name'].' gap',
            'evidence' => $kpi['status_reason'] ?: 'Actual belum memenuhi target berjalan.',
            'source' => $kpi['source']['name'] ?? 'KPI source',
            'severity' => $kpi['status'],
            'finding_type' => 'confirmed',
        ])->all();
        $risks = $issues->take(3)->map(fn (array $kpi) => [
            'type' => $kpi['status'] === 'critical' ? 'risk' : 'watch',
            'title' => $kpi['name'].' memerlukan perhatian',
            'description' => $kpi['status_reason'],
            'evidence' => $kpi['actual_formatted'].' dibanding target periode.',
            'related_kpi' => $kpi['name'],
            'urgency' => $kpi['status'] === 'critical' ? 'high' : 'medium',
        ])->values()->all();
        $decisions = $issues->take(5)->values()->map(fn (array $kpi, int $index) => [
            'priority' => $index === 0 ? 'P1' : ($index < 3 ? 'P2' : 'P3'),
            'action' => 'Tetapkan recovery action untuk '.$kpi['name'].'.',
            'pic_role' => $this->picRole($kpi['code']),
            'timeframe' => $kpi['status'] === 'critical' ? 'Within 48 Hours' : 'This Week',
            'related_kpi' => $kpi['name'],
            'expected_impact' => 'Meningkatkan kendali terhadap gap KPI secara terukur.',
            'reason' => $kpi['status_reason'],
        ])->all();

        return [
            ...$fallback,
            'title' => 'Executive Brief — '.$period['label'],
            'executive_summary' => $fallback['summary'],
            'root_causes' => $causes,
            'risk_opportunity' => $risks,
            'recommended_decisions' => $decisions,
            'recommendations' => collect($decisions)->pluck('action')->all(),
            'confidence' => $this->confidence($payload['kpis']),
            'generated_at' => now()->toIso8601String(),
            'generation_type' => 'local_fallback',
            'is_ai_generated' => false,
            'provider' => null,
            'fingerprint' => $fingerprint,
        ];
    }

    private function confidence(array $kpis): array
    {
        if ($kpis === []) {
            return ['label' => 'Unavailable', 'score' => null, 'source_count' => 0, 'helper' => 'Confidence merepresentasikan kelengkapan dan freshness data, bukan jaminan kesimpulan AI.'];
        }
        $rows = collect($kpis);
        $actual = $rows->filter(fn ($kpi) => $kpi['actual'] !== null)->count() / $rows->count();
        $target = $rows->filter(fn ($kpi) => $kpi['period_target'] !== null)->count() / $rows->count();
        $previous = $rows->filter(fn ($kpi) => $kpi['previous_period'] !== null)->count() / $rows->count();
        $fresh = $rows->filter(fn ($kpi) => ! empty($kpi['source']['last_recorded_at']))->count() / $rows->count();
        $score = (int) round(($actual * 45 + $target * 25 + $previous * 15 + $fresh * 15));

        return ['label' => $score >= 80 ? 'High' : ($score >= 55 ? 'Moderate' : 'Low'), 'score' => $score, 'source_count' => $rows->pluck('source.name')->filter()->unique()->count(), 'helper' => 'Confidence merepresentasikan kelengkapan dan freshness data, bukan jaminan kesimpulan AI.'];
    }

    private function normalizeRootCauses(mixed $items): array
    {
        return collect(is_array($items) ? $items : [])->take(4)->map(fn ($x, $i) => ['rank' => $i + 1, 'title' => trim((string) ($x['title'] ?? '')), 'evidence' => trim((string) ($x['evidence'] ?? '')), 'source' => trim((string) ($x['source'] ?? '')), 'severity' => in_array($x['severity'] ?? '', ['critical', 'attention', 'unavailable'], true) ? $x['severity'] : 'attention', 'finding_type' => ($x['finding_type'] ?? '') === 'confirmed' ? 'confirmed' : 'indication'])->filter(fn ($x) => $x['title'] !== '' && $x['evidence'] !== '')->values()->all();
    }

    private function normalizeRisks(mixed $items): array
    {
        return collect(is_array($items) ? $items : [])->take(3)->map(fn ($x) => ['type' => in_array($x['type'] ?? '', ['risk', 'opportunity', 'watch'], true) ? $x['type'] : 'watch', 'title' => trim((string) ($x['title'] ?? '')), 'description' => trim((string) ($x['description'] ?? '')), 'evidence' => trim((string) ($x['evidence'] ?? '')), 'related_kpi' => trim((string) ($x['related_kpi'] ?? '')), 'urgency' => in_array($x['urgency'] ?? '', ['high', 'medium', 'low'], true) ? $x['urgency'] : 'medium'])->filter(fn ($x) => $x['title'] !== '')->values()->all();
    }

    private function normalizeDecisions(mixed $items): array
    {
        return collect(is_array($items) ? $items : [])->take(5)->map(fn ($x) => ['priority' => in_array($x['priority'] ?? '', ['P1', 'P2', 'P3'], true) ? $x['priority'] : 'P3', 'action' => trim((string) ($x['action'] ?? '')), 'pic_role' => trim((string) ($x['pic_role'] ?? 'Management')), 'timeframe' => in_array($x['timeframe'] ?? '', ['Today', 'Within 48 Hours', 'This Week', 'Monitor'], true) ? $x['timeframe'] : 'Monitor', 'related_kpi' => trim((string) ($x['related_kpi'] ?? '')), 'expected_impact' => trim((string) ($x['expected_impact'] ?? 'Dampak akan dipantau melalui KPI terkait.')), 'reason' => trim((string) ($x['reason'] ?? ''))])->filter(fn ($x) => $x['action'] !== '')->values()->all();
    }

    private function picRole(string $code): string
    {
        return match ($code) {
            'confirmed_revenue' => 'Finance Lead','marketing_spend','total_leads' => 'Marketing Lead','student_completion_rate' => 'Academic Lead','closed_deals','paid_students' => 'Sales Lead',default => 'Management'
        };
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
