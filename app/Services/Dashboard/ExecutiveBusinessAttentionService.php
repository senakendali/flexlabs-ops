<?php

namespace App\Services\Dashboard;

class ExecutiveBusinessAttentionService
{
    public const STATES = ['all', 'open', 'monitoring', 'resolved'];

    public function __construct(private readonly ExecutiveDashboardService $dashboardService) {}

    public function getData(string $period, string $division = 'all', string $state = 'open', ?string $issueKey = null): array
    {
        $division = $division === 'all' || array_key_exists($division, ExecutiveKpiScorecardService::DIVISIONS) ? $division : 'all';
        $state = in_array($state, self::STATES, true) ? $state : 'open';
        $source = $this->dashboardService->getData(['month' => $period]);
        $brief = $source['executiveBrief'] ?? [];
        $issues = collect($source['kpiScorecard'] ?? [])
            ->filter(fn (array $kpi) => in_array($kpi['status'] ?? '', ['critical', 'watch'], true))
            ->map(fn (array $kpi) => $this->issue($kpi, $brief))
            ->when($division !== 'all' && $division !== 'company', fn ($rows) => $rows->where('division', $division))
            ->when($state !== 'all', fn ($rows) => $rows->where('state', $state))
            ->sortBy([['priority_order', 'asc'], ['impact_order', 'desc'], ['key', 'asc']])
            ->values();
        $selected = $issues->firstWhere('key', $issueKey) ?? $issues->first();

        return [
            'filters' => ['period' => $source['period']['month'], 'division' => $division, 'state' => $state, 'issue' => $selected['key'] ?? null],
            'period' => $source['period'],
            'divisions' => $this->divisions(),
            'states' => [['key' => 'open', 'label' => 'Open issues'], ['key' => 'monitoring', 'label' => 'Monitoring'], ['key' => 'resolved', 'label' => 'Resolved'], ['key' => 'all', 'label' => 'All states']],
            'issues' => $issues->all(),
            'selectedIssue' => $selected,
            'summary' => [
                'critical' => $issues->where('severity', 'critical')->count(),
                'high' => $issues->where('severity', 'high')->count(),
                'medium' => $issues->where('severity', 'medium')->count(),
                'resolved' => $issues->where('state', 'resolved')->count(),
            ],
            'hasAvailableKpi' => collect($source['kpiScorecard'] ?? [])->contains(fn ($kpi) => in_array($kpi['status'] ?? '', ['healthy', 'watch', 'critical'], true)),
        ];
    }

    private function issue(array $kpi, array $brief): array
    {
        $isCritical = $kpi['status'] === 'critical';
        $root = collect($brief['root_causes'] ?? [])->first(fn ($item) => is_array($item) && $this->matches($item['source'] ?? '', $kpi));
        $decision = collect($brief['recommended_decisions'] ?? [])->first(fn ($item) => is_array($item) && $this->matches($item['related_kpi'] ?? '', $kpi));
        $risk = collect($brief['risk_opportunity'] ?? [])->first(fn ($item) => is_array($item) && $this->matches($item['related_kpi'] ?? '', $kpi));
        $target = $kpi['expected_to_date'] ?? $kpi['target_value'] ?? null;
        $actual = $kpi['actual_value'] ?? null;
        $gap = $actual !== null && $target !== null ? ($kpi['direction'] === 'lower' ? $target - $actual : $actual - $target) : null;

        return [
            'key' => $kpi['code'].'_attention', 'kpi_code' => $kpi['code'], 'title' => $kpi['name'],
            'division' => $this->group($kpi['code']), 'division_label' => $this->owner($kpi['division'] ?? ''),
            'owner' => $decision['pic_role'] ?? $this->owner($kpi['division'] ?? '').' Lead',
            'severity' => $isCritical ? 'critical' : 'high', 'severity_label' => $isCritical ? 'Critical' : 'High',
            'priority_order' => $isCritical ? 1 : 2, 'impact_order' => abs((float) ($kpi['pace_percentage'] ?? 0)),
            'state' => $isCritical ? 'open' : 'monitoring', 'state_label' => $isCritical ? 'Open' : 'Monitoring',
            'actual' => $actual, 'target' => $target, 'gap' => $gap,
            'metric_summary' => 'Actual '.$kpi['actual_formatted'].' · Target '.$kpi['target_formatted'],
            'supporting_metric' => $kpi['status_reason'],
            'root_cause' => $root['evidence'] ?? null,
            'root_cause_type' => ($root['finding_type'] ?? null) === 'confirmed' ? 'confirmed' : ($root ? 'indication' : null),
            'business_impact' => $risk['description'] ?? null,
            'recommendation' => $decision['action'] ?? ($kpi['status_reason'] ? 'Review gap '.$kpi['name'].' dan tetapkan recovery action terukur.' : null),
            'recommendation_type' => ($brief['is_ai_generated'] ?? false) && $decision ? 'AI Recommendation' : 'Recommended Action',
            'expected_impact' => $decision['expected_impact'] ?? null,
            'source' => $kpi['source_label'], 'source_status' => $kpi['actual_available'] ? 'available' : 'unavailable',
            'updated_at' => $kpi['last_recorded_at'],
        ];
    }

    private function matches(string $reference, array $kpi): bool
    {
        $reference = mb_strtolower(trim($reference));

        return $reference !== '' && in_array($reference, [mb_strtolower($kpi['name']), mb_strtolower($kpi['code']), mb_strtolower($kpi['source_label'] ?? '')], true);
    }

    private function group(string $code): string
    {
        foreach (ExecutiveKpiScorecardService::DIVISIONS as $key => $config) {
            if ($key !== 'company' && in_array($code, $config['codes'] ?? [], true)) {
                return $key;
            }
        }

        return 'company';
    }

    private function owner(string $division): string
    {
        return match ($division) {
            'sales' => 'Sales', 'marketing' => 'Marketing', 'academic' => 'Academic', 'finance' => 'Finance', 'hr' => 'HR', 'operations' => 'Operations', default => 'Management'
        };
    }

    private function divisions(): array
    {
        return [['key' => 'all', 'label' => 'All divisions'], ...collect(ExecutiveKpiScorecardService::DIVISIONS)->except('company')->map(fn ($item, $key) => ['key' => $key, 'label' => $item['label']])->values()->all()];
    }
}
