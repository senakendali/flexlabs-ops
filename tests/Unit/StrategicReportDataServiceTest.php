<?php

namespace Tests\Unit;

use App\Services\Dashboard\ExecutiveDashboardService;
use App\Services\StrategicReports\StrategicReportDataService;
use Mockery;
use Tests\TestCase;

class StrategicReportDataServiceTest extends TestCase
{
    public function test_zero_denominators_and_missing_academic_sources_are_data_limited(): void
    {
        $dashboard = Mockery::mock(ExecutiveDashboardService::class);
        $dashboard->shouldReceive('getScorecardSourceData')->times(7)->andReturn($this->scorecardSource());
        $dashboard->shouldReceive('getData')->once()->andReturn($this->dashboardData());

        $result = (new StrategicReportDataService($dashboard))->build('monthly', '2026-07');
        $cross = collect($result['cross_functional'])->keyBy('key');
        $kpis = collect($result['kpis'])->keyBy('key');

        $this->assertFalse($cross['cost_per_lead']['available']);
        $this->assertFalse($cross['lead_to_paid_conversion']['available']);
        $this->assertSame('Not Available', $cross['cost_per_lead']['formatted']);
        $this->assertFalse($kpis['student_program_completion_rate']['available']);
        $this->assertSame('Data Limited', $kpis['student_program_completion_rate']['status_label']);
        $this->assertNotEmpty($result['limitations']);
    }

    private function scorecardSource(): array
    {
        return ['period' => ['label' => 'Juli 2026'], 'kpiScorecard' => [
            ['code' => 'total_leads', 'name' => 'Total Leads', 'unit' => 'number', 'direction' => 'higher', 'actual_available' => true, 'has_data' => true, 'actual_value' => 0, 'actual_formatted' => '0', 'target_value' => 100, 'status' => 'critical', 'status_label' => 'Critical', 'trend' => ['available' => false], 'source_label' => 'Sales'],
            ['code' => 'marketing_spend', 'name' => 'Marketing Spend', 'unit' => 'currency', 'direction' => 'lower', 'actual_available' => true, 'has_data' => true, 'actual_value' => 100, 'actual_formatted' => 'Rp 100', 'target_value' => 80, 'status' => 'critical', 'status_label' => 'Critical', 'trend' => ['available' => false], 'source_label' => 'Ads'],
            ['code' => 'paid_students', 'name' => 'Paid Students', 'unit' => 'number', 'direction' => 'higher', 'actual_available' => true, 'has_data' => true, 'actual_value' => 0, 'actual_formatted' => '0', 'target_value' => 10, 'status' => 'critical', 'status_label' => 'Critical', 'trend' => ['available' => false], 'source_label' => 'Payments'],
        ]];
    }

    private function dashboardData(): array
    {
        return [...$this->scorecardSource(), 'businessHealth' => [], 'dataFreshness' => [], 'executiveBrief' => ['summary' => 'Local summary', 'confidence' => ['label' => 'Low', 'score' => 20], 'risk_opportunity' => [], 'recommended_decisions' => []]];
    }
}
