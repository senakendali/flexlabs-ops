<?php

namespace Tests\Unit;

use App\Services\Dashboard\ExecutiveBusinessAttentionService;
use App\Services\Dashboard\ExecutiveDashboardService;
use Mockery;
use Tests\TestCase;

class ExecutiveBusinessAttentionServiceTest extends TestCase
{
    public function test_only_scoreable_issues_are_sorted_and_ai_detail_is_reused(): void
    {
        $dashboard = Mockery::mock(ExecutiveDashboardService::class);
        $dashboard->shouldReceive('getData')->once()->with(['month' => '2026-07'])->andReturn($this->source());
        $data = (new ExecutiveBusinessAttentionService($dashboard))->getData('2026-07', 'all', 'all');

        $this->assertCount(2, $data['issues']);
        $this->assertSame('confirmed_revenue_attention', $data['issues'][0]['key']);
        $this->assertSame('AI Recommendation', $data['issues'][0]['recommendation_type']);
        $this->assertSame('indication', $data['issues'][0]['root_cause_type']);
        $this->assertSame(1, $data['summary']['critical']);
        $this->assertSame(0, $data['summary']['resolved']);
        $this->assertNotContains('student_completion_rate_attention', array_column($data['issues'], 'key'));
    }

    public function test_invalid_issue_selects_first_valid_item_and_filters_are_safe(): void
    {
        $dashboard = Mockery::mock(ExecutiveDashboardService::class);
        $dashboard->shouldReceive('getData')->once()->andReturn($this->source());
        $data = (new ExecutiveBusinessAttentionService($dashboard))->getData('2026-07', 'bad', 'bad', 'missing');
        $this->assertSame('all', $data['filters']['division']);
        $this->assertSame('open', $data['filters']['state']);
        $this->assertSame('confirmed_revenue_attention', $data['selectedIssue']['key']);
    }

    private function source(): array
    {
        $item = fn ($code, $name, $status, $division) => ['code' => $code, 'name' => $name, 'status' => $status, 'division' => $division, 'direction' => 'higher', 'actual_value' => $status === 'unavailable' ? null : 60, 'target_value' => 100, 'expected_to_date' => 80, 'actual_formatted' => $status === 'unavailable' ? 'Unavailable' : '60', 'target_formatted' => '100', 'pace_percentage' => 75, 'status_reason' => 'Below target pace.', 'source_label' => 'KPI Source', 'actual_available' => $status !== 'unavailable', 'last_recorded_at' => '2026-07-20'];

        return ['period' => ['month' => '2026-07', 'label' => 'Juli 2026'], 'kpiScorecard' => [$item('confirmed_revenue', 'Confirmed Revenue', 'critical', 'sales'), $item('marketing_spend', 'Marketing Spend', 'watch', 'marketing'), $item('student_completion_rate', 'Student On-Track Rate', 'unavailable', 'academic')], 'executiveBrief' => ['is_ai_generated' => true, 'root_causes' => [['source' => 'Confirmed Revenue', 'evidence' => 'Indikasi collection perlu diperiksa.', 'finding_type' => 'indication']], 'recommended_decisions' => [['related_kpi' => 'Confirmed Revenue', 'action' => 'Review collection.', 'pic_role' => 'Finance Lead', 'expected_impact' => 'Gap lebih terkendali.']], 'risk_opportunity' => []]];
    }
}
