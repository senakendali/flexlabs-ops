<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\StrategicReports\StrategicReportDataService;
use App\Services\StrategicReports\StrategicReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Mockery;
use Tests\TestCase;

class StrategicReportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('strategic_reports', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('period_type');
            $t->date('period_start');
            $t->date('period_end');
            $t->unsignedInteger('revision');
            $t->string('status');
            $t->string('overall_business_health')->nullable();
            $t->string('data_confidence')->nullable();
            $t->unsignedInteger('data_coverage')->nullable();
            foreach (['kpi_snapshot', 'centre_performance_snapshot', 'trend_snapshot', 'cross_functional_snapshot', 'wins', 'risks', 'opportunities', 'management_decisions', 'action_plan', 'data_freshness', 'source_limitations', 'ai_metadata'] as $column) {
                $t->text($column)->nullable();
            }$t->text('executive_summary')->nullable();
            $t->unsignedBigInteger('generated_by')->nullable();
            $t->timestamp('generated_at')->nullable();
            $t->unsignedBigInteger('finalized_by')->nullable();
            $t->timestamp('finalized_at')->nullable();
            $t->timestamps();
        });
    }

    public function test_finalized_snapshot_is_immutable_and_new_generation_creates_revision(): void
    {
        $data = Mockery::mock(StrategicReportDataService::class);
        $data->shouldReceive('build')->once()->andReturn($this->snapshot('First summary'));
        $service = new StrategicReportService($data);
        $user = (new User)->forceFill(['id' => 1]);
        $first = $service->generate('monthly', '2026-07', $user);
        $service->finalize($first, $user);
        $this->expectException(LogicException::class);
        $service->regenerate($first->fresh(), $user);
    }

    public function test_generation_after_finalized_report_creates_revision_without_overwriting_snapshot(): void
    {
        $data = Mockery::mock(StrategicReportDataService::class);
        $data->shouldReceive('build')->twice()->andReturn($this->snapshot('First summary'), $this->snapshot('Second summary'));
        $service = new StrategicReportService($data);
        $user = (new User)->forceFill(['id' => 1]);
        $first = $service->generate('monthly', '2026-07', $user);
        $service->finalize($first, $user);
        $second = $service->generate('monthly', '2026-07', $user);
        $this->assertSame(1, $first->fresh()->revision);
        $this->assertSame('First summary', $first->fresh()->executive_summary);
        $this->assertSame(2, $second->revision);
        $this->assertSame('Second summary', $second->executive_summary);
        $this->assertSame('draft', $second->status);
    }

    private function snapshot(string $summary): array
    {
        return ['identity' => ['period_type' => 'monthly', 'period_start' => '2026-07-01', 'period_end' => '2026-07-31', 'period_label' => 'Juli 2026'], 'kpis' => [], 'centres' => [], 'trends' => [], 'cross_functional' => [], 'health' => 'data_limited', 'coverage' => 0, 'confidence' => ['label' => 'Low'], 'freshness' => [], 'limitations' => [], 'brief' => ['summary' => $summary, 'risk_opportunity' => [], 'recommended_decisions' => [], 'generation_type' => 'local_fallback']];
    }
}
