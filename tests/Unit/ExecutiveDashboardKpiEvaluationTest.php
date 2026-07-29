<?php

namespace Tests\Unit;

use App\Models\KpiDefinition;
use App\Services\Dashboard\ExecutiveBriefAiService;
use App\Services\Dashboard\ExecutiveDashboardService;
use App\Services\Dashboard\StudentOnTrackRateCalculator;
use App\Services\Trello\TrelloDashboardStatsService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ExecutiveDashboardKpiEvaluationTest extends TestCase
{
    public function test_empty_on_track_denominator_is_no_data_and_never_critical(): void
    {
        $service = new ExecutiveDashboardService(
            $this->createMock(TrelloDashboardStatsService::class),
            $this->createMock(ExecutiveBriefAiService::class),
            new StudentOnTrackRateCalculator()
        );
        $definition = new KpiDefinition([
            'code' => 'student_completion_rate',
            'direction' => KpiDefinition::DIRECTION_HIGHER,
        ]);
        $actual = [
            'value' => 0.0,
            'available' => true,
            'has_data' => false,
            'message' => 'Tidak ada student aktif dengan timeline batch yang dapat dinilai pada periode ini.',
        ];
        $period = [
            'is_future' => false,
            'elapsed_ratio' => 1.0,
        ];
        $method = new ReflectionMethod($service, 'evaluateKpi');

        $result = $method->invoke($service, $definition, 90.0, $actual, $period);

        $this->assertSame('no_data', $result['status']);
        $this->assertNotSame('critical', $result['status']);
        $this->assertNull($result['achievement_percentage']);
    }
}
