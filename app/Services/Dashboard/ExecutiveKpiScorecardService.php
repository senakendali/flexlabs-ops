<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Collection;

class ExecutiveKpiScorecardService
{
    public const DIVISIONS = [
        'company' => ['label' => 'Company', 'codes' => null],
        'growth' => ['label' => 'Growth', 'codes' => ['total_leads', 'closed_deals', 'paid_students', 'marketing_spend']],
        'learning' => ['label' => 'Learning', 'codes' => ['student_completion_rate']],
        'talent' => ['label' => 'Talent', 'codes' => []],
        'finance' => ['label' => 'Finance', 'codes' => ['confirmed_revenue']],
        'operations' => ['label' => 'Operations', 'codes' => []],
    ];

    public function __construct(private readonly ExecutiveDashboardService $dashboardService) {}

    public function getData(string $period, string $division = 'company'): array
    {
        $division = array_key_exists($division, self::DIVISIONS) ? $division : 'company';
        $source = $this->dashboardService->getScorecardSourceData(['month' => $period]);
        $all = collect($source['kpiScorecard']);
        $codes = self::DIVISIONS[$division]['codes'];
        $items = ($codes === null ? $all : $all->whereIn('code', $codes))
            ->map(fn (array $kpi) => $this->normalize($kpi))
            ->values();

        return [
            ...$source,
            'filters' => ['period' => $source['period']['month'], 'division' => $division],
            'divisions' => collect(self::DIVISIONS)->map(fn ($config, $key) => [
                'key' => $key,
                'label' => $config['label'],
                'count' => $config['codes'] === null ? $all->count() : $all->whereIn('code', $config['codes'])->count(),
            ])->values()->all(),
            'divisionLabel' => self::DIVISIONS[$division]['label'],
            'scorecard' => $items->all(),
            'scorecardSummary' => $this->summary($items),
            'statusLegend' => 'On track mengikuti target berjalan · Watch mendekati target berjalan · Critical tertinggal dari target berjalan · No data tidak dinilai',
        ];
    }

    private function normalize(array $kpi): array
    {
        $trend = $kpi['trend'] ?? [];
        $change = $trend['change_value'] ?? null;
        $isRate = ($kpi['unit'] ?? null) === 'percentage';
        $display = '—';
        $direction = 'neutral';
        $previousAchievement = $this->previousAchievement($kpi, $trend);

        if ($trend['available'] ?? false) {
            if ($trend['is_new'] ?? false) {
                $display = 'New';
                $direction = 'up';
            } elseif ($change !== null && abs((float) $change) < 0.0001) {
                $display = '→ Stable';
            } elseif ($isRate && $change !== null) {
                $direction = $change > 0 ? 'up' : 'down';
                $display = ($change > 0 ? '↑ ' : '↓ ').number_format(abs((float) $change), 1, ',', '.').' pt';
            } elseif (($trend['change_percentage'] ?? null) !== null) {
                $value = (float) $trend['change_percentage'];
                $direction = $value > 0 ? 'up' : ($value < 0 ? 'down' : 'neutral');
                $display = $value === 0.0 ? '→ Stable' : ($value > 0 ? '↑ ' : '↓ ').number_format(abs($value), 1, ',', '.').'%';
            }
        }

        return [
            ...$kpi,
            'key' => $kpi['code'],
            'label' => $kpi['name'],
            'owner' => $this->owner((string) ($kpi['division'] ?? '')),
            'group' => $this->group((string) $kpi['code']),
            'scoring_direction' => ($kpi['direction'] ?? 'higher') === 'lower' ? 'lower_is_better' : 'higher_is_better',
            'progress_width' => min(100, max(0, (float) ($kpi['achievement_percentage'] ?? 0))),
            'scoreable' => in_array($kpi['status'] ?? '', ['healthy', 'watch', 'critical'], true)
                && ($kpi['achievement_percentage'] ?? null) !== null,
            'previous_achievement' => $previousAchievement,
            'trend_display' => [
                'display' => $display,
                'direction' => $direction,
                'tone' => ($trend['is_positive'] ?? null) === true ? 'positive' : (($trend['is_positive'] ?? null) === false ? 'negative' : 'neutral'),
            ],
        ];
    }

    private function summary(Collection $items): array
    {
        $scored = $items->where('scoreable', true);
        $count = $scored->count();
        $average = $count > 0 ? round((float) $scored->avg('achievement_percentage'), 1) : null;
        $previous = $scored->whereNotNull('previous_achievement');
        $previousAverage = $previous->isNotEmpty() ? round((float) $previous->avg('previous_achievement'), 1) : null;
        $statusCount = fn (string $status) => $scored->where('status', $status)->count();
        $percent = fn (int $value) => $count > 0 ? round($value / $count * 100, 1) : 0.0;

        return [
            'average_achievement' => $average,
            'average_change' => $average !== null && $previousAverage !== null ? round($average - $previousAverage, 1) : null,
            'scoreable_count' => $count,
            'achieved_count' => $healthy = $statusCount('healthy'),
            'achieved_percentage' => $percent($healthy),
            'attention_count' => $watch = $statusCount('watch'),
            'attention_percentage' => $percent($watch),
            'critical_count' => $critical = $statusCount('critical'),
            'critical_percentage' => $percent($critical),
            'unavailable_count' => $items->count() - $count,
        ];
    }

    private function previousAchievement(array $kpi, array $trend): ?float
    {
        $previous = $trend['previous_value'] ?? null;
        $target = $kpi['target_value'] ?? null;

        if ($previous === null || $target === null || (float) $target <= 0) {
            return null;
        }

        if (($kpi['direction'] ?? 'higher') === 'lower') {
            return (float) $previous <= 0 ? 100.0 : min(100, ((float) $target / (float) $previous) * 100);
        }

        return min(100, ((float) $previous / (float) $target) * 100);
    }

    private function group(string $code): string
    {
        foreach (self::DIVISIONS as $key => $config) {
            if ($key !== 'company' && in_array($code, $config['codes'] ?? [], true)) {
                return $key;
            }
        }

        return 'company';
    }

    private function owner(string $division): string
    {
        return match ($division) {
            'sales' => 'Sales', 'marketing' => 'Marketing', 'academic' => 'Academic',
            'finance' => 'Finance', 'hr' => 'HR', 'operations' => 'Operations', default => 'Company',
        };
    }
}
