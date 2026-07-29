<?php

namespace App\Services\StrategicReports;

use App\Models\StrategicReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use LogicException;

class StrategicReportService
{
    public function __construct(private readonly StrategicReportDataService $dataService) {}

    public function library(array $filters): array
    {
        $query = StrategicReport::query()->with(['generatedBy:id,name', 'finalizedBy:id,name'])->latest('period_start')->latest('revision');
        $query->when($filters['type'] ?? null, fn (Builder $q, $value) => $q->where('period_type', $value));
        $query->when($filters['year'] ?? null, fn (Builder $q, $value) => $q->whereYear('period_start', $value));
        $query->when($filters['status'] ?? null, fn (Builder $q, $value) => $q->where('status', $value));
        $query->when($filters['search'] ?? null, fn (Builder $q, $value) => $q->where('title', 'like', '%'.$value.'%'));

        return ['reports' => $query->paginate(12)->withQueryString(), 'filters' => $filters, 'years' => StrategicReport::query()->selectRaw('YEAR(period_start) year')->distinct()->orderByDesc('year')->pluck('year')];
    }

    public function generate(string $type, string $period, User $user): StrategicReport
    {
        $snapshot = $this->dataService->build($type, $period);
        $identity = $snapshot['identity'];

        return DB::transaction(function () use ($type, $user, $snapshot, $identity) {
            $draft = StrategicReport::query()->where('period_type', $type)->whereDate('period_start', $identity['period_start'])->whereDate('period_end', $identity['period_end'])->where('status', StrategicReport::STATUS_DRAFT)->latest('revision')->lockForUpdate()->first();
            $report = $draft ?: new StrategicReport(['revision' => (int) StrategicReport::query()->where('period_type', $type)->whereDate('period_start', $identity['period_start'])->whereDate('period_end', $identity['period_end'])->max('revision') + 1]);
            $brief = $snapshot['brief'];
            $wins = collect($snapshot['kpis'])->where('status', 'healthy')->take(3)->map(fn ($k) => ['title' => $k['label'].' on track', 'description' => $k['actual_formatted'].' terhadap target '.$k['target_formatted'], 'centre' => $this->centre($k['key']), 'evidence' => $k['status_label'], 'impact' => 'positive'])->values()->all();
            $risks = collect($brief['risk_opportunity'] ?? [])->where('type', 'risk')->take(3)->values()->all();
            $opportunities = collect($brief['risk_opportunity'] ?? [])->where('type', 'opportunity')->take(3)->values()->all();
            $decisions = collect($brief['recommended_decisions'] ?? [])->take(5)->map(fn ($d) => [...$d, 'status' => 'pending', 'centre' => $this->centreFromName($d['related_kpi'] ?? '')])->values()->all();
            $actions = collect($decisions)->map(fn ($d) => ['action' => $d['action'], 'centre' => $d['centre'], 'pic' => $d['pic_role'], 'deadline' => $d['timeframe'], 'expected_impact' => $d['expected_impact'], 'priority' => $d['priority'], 'status' => 'pending'])->all();
            $report->fill([
                'title' => ($type === StrategicReport::TYPE_QUARTERLY ? 'Quarterly' : 'Monthly').' Strategic Report · '.$identity['period_label'], 'period_type' => $type,
                'period_start' => $identity['period_start'], 'period_end' => $identity['period_end'], 'status' => StrategicReport::STATUS_DRAFT,
                'overall_business_health' => $snapshot['health'], 'data_confidence' => strtolower($snapshot['confidence']['label'] ?? 'unavailable'), 'data_coverage' => $snapshot['coverage'],
                'kpi_snapshot' => $snapshot['kpis'], 'centre_performance_snapshot' => $snapshot['centres'], 'trend_snapshot' => $snapshot['trends'], 'cross_functional_snapshot' => $snapshot['cross_functional'],
                'executive_summary' => $brief['executive_summary'] ?? $brief['summary'] ?? 'Data tersedia untuk evaluasi manajemen, tetapi narasi belum dapat dibuat.',
                'wins' => $wins, 'risks' => $risks, 'opportunities' => $opportunities, 'management_decisions' => $decisions, 'action_plan' => $actions,
                'data_freshness' => $snapshot['freshness'], 'source_limitations' => $snapshot['limitations'],
                'ai_metadata' => ['generation_type' => $brief['generation_type'] ?? 'local_fallback', 'provider' => $brief['provider'] ?? null, 'fingerprint' => $brief['fingerprint'] ?? null, 'source_generated_at' => $brief['generated_at'] ?? null],
                'generated_by' => $user->id, 'generated_at' => now(), 'finalized_by' => null, 'finalized_at' => null,
            ])->save();

            return $report->fresh();
        });
    }

    public function regenerate(StrategicReport $report, User $user): StrategicReport
    {
        if ($report->isFinalized()) {
            throw new LogicException('Finalized report tidak dapat di-regenerate. Buat revision baru.');
        }

        return $this->generate($report->period_type, $report->period_start->format('Y-m'), $user);
    }

    public function finalize(StrategicReport $report, User $user): StrategicReport
    {
        return DB::transaction(function () use ($report, $user) {
            $locked = StrategicReport::query()->lockForUpdate()->findOrFail($report->id);
            if (! $locked->isFinalized()) {
                $locked->update(['status' => StrategicReport::STATUS_FINALIZED, 'finalized_by' => $user->id, 'finalized_at' => now()]);
            }

            return $locked->fresh();
        });
    }

    private function centre(string $key): string
    {
        return match ($key) {
            'confirmed_revenue' => 'Finance Centre','student_completion_rate','total_active_students','completed_students','at_risk_students','program_enrollments','new_students' => 'Learning Centre','marketing_spend','total_leads','closed_deals','paid_students' => 'Growth Engine',default => 'Executive Center'
        };
    }

    private function centreFromName(string $name): string
    {
        $name = strtolower($name);

        return str_contains($name, 'revenue') ? 'Finance Centre' : (str_contains($name, 'student') ? 'Learning Centre' : (str_contains($name, 'marketing') || str_contains($name, 'lead') || str_contains($name, 'deal') ? 'Growth Engine' : 'Executive Center'));
    }
}
