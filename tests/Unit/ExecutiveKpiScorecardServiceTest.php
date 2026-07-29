<?php

namespace Tests\Unit;

use App\Services\Dashboard\ExecutiveDashboardService;
use App\Services\Dashboard\ExecutiveKpiScorecardService;
use Mockery;
use Tests\TestCase;

class ExecutiveKpiScorecardServiceTest extends TestCase
{
    public function test_company_summary_excludes_unavailable_and_caps_progress_width(): void
    {
        $service = $this->service($this->items());
        $data = $service->getData('2026-07', 'company');

        $this->assertCount(4, $data['scorecard']);
        $this->assertSame(3, $data['scorecardSummary']['scoreable_count']);
        $this->assertSame(1, $data['scorecardSummary']['achieved_count']);
        $this->assertSame(1, $data['scorecardSummary']['attention_count']);
        $this->assertSame(1, $data['scorecardSummary']['critical_count']);
        $this->assertSame(1, $data['scorecardSummary']['unavailable_count']);
        $this->assertSame(85.0, $data['scorecardSummary']['average_achievement']);
        $this->assertEquals(100.0, $data['scorecard'][0]['progress_width']);
    }

    public function test_division_mapping_and_direction_aware_trend_are_preserved(): void
    {
        $data = $this->service($this->items())->getData('2026-07', 'growth');

        $this->assertCount(3, $data['scorecard']);
        $spend = collect($data['scorecard'])->firstWhere('key', 'marketing_spend');
        $rate = collect($data['scorecard'])->firstWhere('key', 'student_completion_rate');
        $this->assertSame('lower_is_better', $spend['scoring_direction']);
        $this->assertSame('positive', $spend['trend_display']['tone']);
        $this->assertNull($rate);

        $learning = $this->service($this->items())->getData('2026-07', 'learning');
        $this->assertSame('Student On-Track Rate', $learning['scorecard'][0]['label']);
        $this->assertSame('↑ 5,0 pt', $learning['scorecard'][0]['trend_display']['display']);
    }

    public function test_unknown_division_falls_back_to_company(): void
    {
        $data = $this->service($this->items())->getData('2026-07', 'unknown');
        $this->assertSame('company', $data['filters']['division']);
    }

    private function service(array $items): ExecutiveKpiScorecardService
    {
        $dashboard = Mockery::mock(ExecutiveDashboardService::class);
        $dashboard->shouldReceive('getScorecardSourceData')->once()->with(['month' => '2026-07'])->andReturn([
            'filters' => ['month' => '2026-07'],
            'period' => ['month' => '2026-07', 'label' => 'Juli 2026'],
            'kpiScorecard' => $items,
        ]);

        return new ExecutiveKpiScorecardService($dashboard);
    }

    private function items(): array
    {
        return [
            $this->item('total_leads', 'Total Leads', 'marketing', 'higher', 'number', 'healthy', 110, 110, 100, true),
            $this->item('closed_deals', 'Closed Deals', 'sales', 'higher', 'number', 'watch', 90, 90, 80, false),
            $this->item('marketing_spend', 'Marketing Spend', 'marketing', 'lower', 'currency', 'critical', 55, 110, 120, true),
            $this->item('student_completion_rate', 'Student On-Track Rate', 'academic', 'higher', 'percentage', 'unavailable', null, 82, 77, true),
        ];
    }

    private function item(string $code, string $name, string $division, string $direction, string $unit, string $status, ?float $achievement, float $actual, float $previous, ?bool $positive): array
    {
        $change = $actual - $previous;

        return [
            'code' => $code, 'name' => $name, 'description' => 'Description', 'division' => $division,
            'direction' => $direction, 'unit' => $unit, 'status' => $status,
            'status_label' => ucfirst($status), 'status_reason' => 'Reason',
            'target_value' => 100, 'target_formatted' => '100', 'actual_formatted' => $status === 'unavailable' ? 'Unavailable' : (string) $actual,
            'achievement_percentage' => $achievement,
            'trend' => ['available' => $positive !== null, 'previous_value' => $previous, 'is_new' => false, 'change_value' => $change, 'change_percentage' => $previous > 0 ? round($change / $previous * 100, 1) : null, 'is_positive' => $positive],
        ];
    }
}
