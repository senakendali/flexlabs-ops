<?php

namespace App\Services\StrategicReports;

use App\Models\StrategicReport;
use App\Services\Dashboard\ExecutiveDashboardService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StrategicReportDataService
{
    public function __construct(private readonly ExecutiveDashboardService $dashboardService) {}

    public function build(string $type, string $period): array
    {
        [$start, $end, $label] = $this->period($type, $period);
        $months = $this->months($start, $end);
        $monthly = collect($months)->map(fn (string $month) => $this->dashboardService->getScorecardSourceData(['month' => $month]));
        $latest = $this->dashboardService->getData(['month' => $months[array_key_last($months)]]);
        $kpis = $this->aggregateKpis($monthly, $type);
        $academic = $this->academicMetrics($start, $end, $latest);
        $allKpis = collect($kpis)->concat($academic)->values()->all();
        $freshness = $latest['dataFreshness'] ?? [];
        $confidence = $latest['executiveBrief']['confidence'] ?? ['label' => 'Unavailable', 'score' => null];

        return [
            'identity' => ['period_type' => $type, 'period_start' => $start->toDateString(), 'period_end' => $end->toDateString(), 'period_label' => $label],
            'kpis' => $allKpis,
            'centres' => $this->centres($latest['businessHealth'] ?? []),
            'trends' => $this->trends($end),
            'cross_functional' => $this->crossFunctional($allKpis),
            'health' => $this->overallHealth($allKpis),
            'coverage' => $this->coverage($allKpis),
            'confidence' => $confidence,
            'freshness' => $freshness,
            'limitations' => $this->limitations($allKpis, $freshness),
            'brief' => $latest['executiveBrief'] ?? [],
        ];
    }

    private function period(string $type, string $period): array
    {
        $month = Carbon::createFromFormat('!Y-m', $period);
        if ($type === StrategicReport::TYPE_QUARTERLY) {
            $start = $month->copy()->firstOfQuarter();
            $end = $month->copy()->lastOfQuarter();

            return [$start, $end, 'Q'.$month->quarter.' '.$month->year];
        }

        return [$month->copy()->startOfMonth(), $month->copy()->endOfMonth(), $month->translatedFormat('F Y')];
    }

    private function months(Carbon $start, Carbon $end): array
    {
        $months = [];
        $cursor = $start->copy()->startOfMonth();
        while ($cursor->lte($end)) {
            $months[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        return $months;
    }

    private function aggregateKpis(Collection $monthly, string $type): array
    {
        return $monthly->flatMap(fn ($data) => $data['kpiScorecard'] ?? [])->groupBy('code')->map(function (Collection $rows) use ($type) {
            $latest = $rows->last();
            $scoreable = $rows->filter(fn ($row) => $row['actual_available'] && $row['has_data']);
            $percentage = ($latest['unit'] ?? '') === 'percentage';
            $actual = $scoreable->isEmpty() ? null : ($percentage ? $scoreable->avg('actual_value') : $scoreable->sum('actual_value'));
            $targets = $rows->pluck('target_value')->filter(fn ($value) => $value !== null);
            $target = $targets->isEmpty() ? null : ($percentage ? $targets->avg() : $targets->sum());
            $achievement = $actual !== null && $target !== null && $target > 0
                ? (($latest['direction'] ?? 'higher') === 'lower' ? min(100, $target / max($actual, .000001) * 100) : min(100, $actual / $target * 100)) : null;

            return [
                'key' => $latest['code'], 'label' => $latest['name'], 'unit' => $latest['unit'], 'direction' => $latest['direction'],
                'actual' => $actual, 'actual_formatted' => $type === StrategicReport::TYPE_MONTHLY ? $latest['actual_formatted'] : $this->format($actual, $latest['unit']),
                'previous_actual' => $latest['trend']['previous_value'] ?? null, 'target' => $target, 'target_formatted' => $this->format($target, $latest['unit']),
                'achievement' => $achievement, 'status' => $latest['status'], 'status_label' => $latest['status_label'],
                'trend' => $latest['trend'], 'source' => $latest['source_label'], 'available' => $actual !== null,
            ];
        })->values()->all();
    }

    private function academicMetrics(Carbon $start, Carbon $end, array $latest): array
    {
        if (! Schema::hasTable('student_enrollments')) {
            return $this->unavailableAcademic('Enrollment table is not available.');
        }
        $base = DB::table('student_enrollments');
        $newStudents = null;
        if (Schema::hasColumn('student_enrollments', 'enrolled_at')) {
            $firstEnrollments = DB::table('student_enrollments')
                ->whereNotNull('enrolled_at')
                ->select('student_id')
                ->selectRaw('MIN(enrolled_at) as first_enrolled_at')
                ->groupBy('student_id');
            $newStudents = DB::query()->fromSub($firstEnrollments, 'first_enrollments')
                ->whereBetween('first_enrolled_at', [$start, $end->copy()->endOfDay()])
                ->count();
        }
        $activeQuery = (clone $base)->where('status', 'active')->where('access_status', 'active');
        if (Schema::hasColumn('student_enrollments', 'enrolled_at')) {
            $activeQuery->where('enrolled_at', '<=', $end->copy()->endOfDay());
        }
        $active = $activeQuery->distinct()->count('student_id');
        $completed = Schema::hasColumn('student_enrollments', 'completed_at') ? (clone $base)->where('status', 'completed')->whereBetween('completed_at', [$start, $end->copy()->endOfDay()])->distinct()->count('student_id') : null;
        $enrollments = Schema::hasColumn('student_enrollments', 'enrolled_at') ? (clone $base)->whereBetween('enrolled_at', [$start, $end->copy()->endOfDay()])->count() : null;
        $onTrack = collect($latest['kpiScorecard'] ?? [])->firstWhere('code', 'student_completion_rate');
        $eligible = data_get($onTrack, 'actual_meta.eligible_students');
        $onTrackCount = data_get($onTrack, 'actual_meta.on_track_students');
        $atRisk = $eligible !== null && $onTrackCount !== null ? max(0, $eligible - $onTrackCount) : null;

        return [
            $this->metric('new_students', 'New Students', $newStudents, 'number', 'Student Enrollments'),
            $this->metric('total_active_students', 'Total Active Students', $active, 'number', 'Active Enrollments'),
            $this->metric('completed_students', 'Completed Students', $completed, 'number', 'Completed Enrollments'),
            $this->metric('at_risk_students', 'At-Risk Students', $atRisk, 'number', 'Student On-Track evaluation'),
            $this->metric('program_enrollments', 'Program Enrollments', $enrollments, 'number', 'Student Enrollments'),
            $this->metric('student_program_completion_rate', 'Student Completion Rate', null, 'percentage', 'Completion cohort', 'Independent completion cohort definition is not configured.'),
        ];
    }

    private function unavailableAcademic(string $reason): array
    {
        return collect(['new_students' => 'New Students', 'total_active_students' => 'Total Active Students', 'completed_students' => 'Completed Students', 'at_risk_students' => 'At-Risk Students', 'program_enrollments' => 'Program Enrollments', 'student_program_completion_rate' => 'Student Completion Rate'])->map(fn ($label, $key) => $this->metric($key, $label, null, $key === 'student_program_completion_rate' ? 'percentage' : 'number', 'Student Enrollments', $reason))->values()->all();
    }

    private function metric(string $key, string $label, ?float $actual, string $unit, string $source, ?string $reason = null): array
    {
        return ['key' => $key, 'label' => $label, 'unit' => $unit, 'direction' => 'higher', 'actual' => $actual, 'actual_formatted' => $this->format($actual, $unit), 'previous_actual' => null, 'target' => null, 'target_formatted' => 'Not configured', 'achievement' => null, 'status' => $actual === null ? 'unavailable' : 'informational', 'status_label' => $actual === null ? 'Data Limited' : 'Available', 'trend' => ['available' => false], 'source' => $source, 'available' => $actual !== null, 'limitation' => $reason];
    }

    private function trends(Carbon $end): array
    {
        $result = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $end->copy()->subMonths($i)->format('Y-m');
            $data = $this->dashboardService->getScorecardSourceData(['month' => $month]);
            foreach ($data['kpiScorecard'] as $kpi) {
                $result[$kpi['code']]['label'] = $kpi['name'];
                $result[$kpi['code']]['unit'] = $kpi['unit'];
                $result[$kpi['code']]['points'][] = ['period' => $data['period']['label'], 'value' => $kpi['actual_available'] && $kpi['has_data'] ? $kpi['actual_value'] : null];
            }
        }

        return array_values($result);
    }

    private function centres(array $centres): array
    {
        return collect($centres)->map(fn ($c) => ['key' => $c['key'], 'name' => $c['name'], 'health_status' => in_array($c['status'], ['healthy', 'watch', 'critical'], true) ? $c['status'] : 'data_limited', 'status_label' => in_array($c['status'], ['healthy', 'watch', 'critical'], true) ? $c['status_label'] : 'Data Limited', 'kpi_summary' => $c['message'] ?? null, 'highlight' => $c['description'] ?? null, 'main_issue' => $c['status'] === 'critical' ? ($c['message'] ?? null) : null, 'trend' => null, 'data_availability' => $c['status'] === 'not_configured' ? 'limited' : 'available'])->values()->all();
    }

    private function crossFunctional(array $kpis): array
    {
        $by = collect($kpis)->keyBy('key');
        $value = fn ($key) => data_get($by->get($key), 'actual');

        return [$this->ratio('cost_per_lead', $value('marketing_spend'), $value('total_leads'), 'currency'), $this->ratio('lead_to_new_student_conversion', $value('new_students'), $value('total_leads'), 'percentage', 100), $this->ratio('lead_to_paid_conversion', $value('paid_students'), $value('total_leads'), 'percentage', 100), $this->ratio('cost_per_paid_student', $value('marketing_spend'), $value('paid_students'), 'currency'), $this->ratio('revenue_to_marketing_spend_ratio', $value('confirmed_revenue'), $value('marketing_spend'), 'decimal'), $this->ratio('at_risk_student_rate', $value('at_risk_students'), $value('total_active_students'), 'percentage', 100)];
    }

    private function ratio(string $key, ?float $numerator, ?float $denominator, string $unit, float $multiplier = 1): array
    {
        $value = $numerator !== null && $denominator !== null && $denominator > 0 ? $numerator / $denominator * $multiplier : null;

        return ['key' => $key, 'label' => ucwords(str_replace('_', ' ', $key)), 'value' => $value, 'formatted' => $this->format($value, $unit), 'available' => $value !== null];
    }

    private function overallHealth(array $kpis): string
    {
        $statuses = collect($kpis)->where('available', true)->pluck('status');

        return $statuses->contains('critical') ? 'critical' : ($statuses->contains('watch') ? 'watch' : ($statuses->isNotEmpty() ? 'healthy' : 'data_limited'));
    }

    private function coverage(array $kpis): int
    {
        return count($kpis) > 0 ? (int) round(collect($kpis)->where('available', true)->count() / count($kpis) * 100) : 0;
    }

    private function limitations(array $kpis, array $freshness): array
    {
        return collect($kpis)->where('available', false)->map(fn ($k) => ['source' => $k['source'], 'message' => $k['limitation'] ?? $k['label'].' is not available.'])->concat(collect($freshness)->where('is_available', false)->map(fn ($f) => ['source' => $f['source_label'], 'message' => $f['message'] ?? 'Source unavailable.']))->unique('source')->values()->all();
    }

    private function format(?float $value, string $unit): string
    {
        if ($value === null) {
            return 'Not Available';
        }

        return match ($unit) {
            'currency' => 'Rp '.number_format($value, 0, ',', '.'),'percentage' => number_format($value, 1, ',', '.').'%', 'decimal' => number_format($value, 2, ',', '.'),default => number_format($value, 0, ',', '.')
        };
    }
}
