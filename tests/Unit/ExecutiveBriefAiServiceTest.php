<?php

namespace Tests\Unit;

use App\Services\Ai\GeminiClientService;
use App\Services\Dashboard\ExecutiveBriefAiService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class ExecutiveBriefAiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_it_sends_all_kpis_once_and_reuses_the_fingerprint_cache(): void
    {
        $client = Mockery::mock(GeminiClientService::class);
        $client->shouldReceive('generateJson')
            ->once()
            ->withArgs(function (string $prompt): bool {
                $this->assertStringContainsString('Revenue', $prompt);
                $this->assertStringContainsString('Student On-Track Rate', $prompt);
                $this->assertStringContainsString('"status": "unavailable"', $prompt);
                $this->assertStringContainsString('"previous_period"', $prompt);
                $this->assertStringContainsString('"on_track_students": 0', $prompt);
                $this->assertStringContainsString('"eligible_students": 0', $prompt);
                $this->assertStringContainsString('"average_actual_progress": null', $prompt);
                $this->assertStringContainsString('"unavailable_reason": "Snapshot belum tersedia."', $prompt);

                return true;
            })
            ->andReturn([
                'headline' => 'Revenue perlu tindakan segera',
                'executive_summary' => 'Revenue masih tertinggal, sedangkan Student On-Track Rate belum dapat dinilai.',
                'root_causes' => [
                    ['rank' => 1, 'title' => 'Revenue gap', 'evidence' => 'Revenue turun dibanding periode sebelumnya.', 'source' => 'Revenue', 'severity' => 'critical', 'finding_type' => 'confirmed'],
                ],
                'risk_opportunity' => [
                    ['type' => 'risk', 'title' => 'Revenue tertinggal', 'description' => 'Gap perlu ditutup.', 'evidence' => 'Achievement 60%.', 'related_kpi' => 'Revenue', 'urgency' => 'high'],
                ],
                'recommended_decisions' => [
                    ['priority' => 'P1', 'action' => 'Tetapkan recovery action Revenue.', 'pic_role' => 'Finance Lead', 'timeframe' => 'Within 48 Hours', 'related_kpi' => 'Revenue', 'expected_impact' => 'Gap lebih terkendali.', 'reason' => 'Revenue critical.'],
                ],
            ]);

        $service = new ExecutiveBriefAiService($client);
        $scorecard = $this->scorecard();
        $period = $this->period();
        $fallback = $this->fallback();

        $first = $service->generate($scorecard, $period, $fallback);
        $second = $service->generate($scorecard, $period, $fallback);

        $this->assertTrue($first['is_ai_generated']);
        $this->assertSame('ai', $first['generation_type']);
        $this->assertSame($first['fingerprint'], $second['fingerprint']);
        $this->assertSame('Within 48 Hours', $second['priority']);
        $this->assertSame('critical', $first['root_causes'][0]['severity']);
        $this->assertSame(2, $first['confidence']['source_count']);
        $this->assertSame('Moderate', $first['confidence']['label']);
        $this->assertSame(63, $first['confidence']['score']);
    }

    public function test_it_returns_and_temporarily_caches_local_fallback_when_ai_fails(): void
    {
        $client = Mockery::mock(GeminiClientService::class);
        $client->shouldReceive('generateJson')
            ->once()
            ->andThrow(new \RuntimeException('Provider unavailable'));

        $service = new ExecutiveBriefAiService($client);

        $first = $service->generate($this->scorecard(), $this->period(), $this->fallback());
        $second = $service->generate($this->scorecard(), $this->period(), $this->fallback());

        $this->assertFalse($first['is_ai_generated']);
        $this->assertSame('local_fallback', $first['generation_type']);
        $this->assertSame($first['fingerprint'], $second['fingerprint']);
        $this->assertSame('critical', $first['root_causes'][0]['severity']);
        $this->assertNotContains('unavailable', array_column($first['root_causes'], 'severity'));
    }

    public function test_changed_kpi_data_creates_a_new_fingerprint_and_ai_request(): void
    {
        $client = Mockery::mock(GeminiClientService::class);
        $response = [
            'headline' => 'Revenue perlu perhatian',
            'executive_summary' => 'Data menunjukkan gap Revenue.',
            'root_causes' => [],
            'risk_opportunity' => [],
            'recommended_decisions' => [[
                'priority' => 'P1', 'action' => 'Tinjau Revenue.', 'pic_role' => 'Finance Lead',
                'timeframe' => 'This Week', 'related_kpi' => 'Revenue',
                'expected_impact' => 'Kendali gap membaik.', 'reason' => 'Gap tersedia pada KPI.',
            ]],
        ];
        $client->shouldReceive('generateJson')->twice()->andReturn($response);
        $service = new ExecutiveBriefAiService($client);

        $first = $service->generate($this->scorecard(), $this->period(), $this->fallback());
        $changed = $this->scorecard();
        $changed[0]['actual_value'] = 61;
        $second = $service->generate($changed, $this->period(), $this->fallback());

        $this->assertNotSame($first['fingerprint'], $second['fingerprint']);
    }

    private function scorecard(): array
    {
        return [
            [
                'code' => 'confirmed_revenue',
                'name' => 'Revenue',
                'unit' => 'currency',
                'direction' => 'higher',
                'actual_value' => 60,
                'actual_formatted' => 'Rp60',
                'actual_available' => true,
                'has_data' => true,
                'target_value' => 100,
                'expected_to_date' => 80,
                'achievement_percentage' => 60,
                'status' => 'critical',
                'status_reason' => 'Actual masih di bawah target berjalan.',
                'trend' => [
                    'available' => true,
                    'previous_value' => 70,
                    'change_value' => -10,
                    'change_percentage' => -14.3,
                ],
                'source_label' => 'Payments',
                'source_message' => null,
                'last_recorded_at' => '2026-07-20T00:00:00+07:00',
            ],
            [
                'code' => 'student_completion_rate',
                'name' => 'Student On-Track Rate',
                'unit' => 'percentage',
                'direction' => 'higher',
                'actual_value' => null,
                'actual_formatted' => 'Unavailable',
                'actual_available' => false,
                'has_data' => false,
                'target_value' => 90,
                'expected_to_date' => null,
                'achievement_percentage' => null,
                'status' => 'unavailable',
                'status_reason' => 'Snapshot belum tersedia.',
                'trend' => ['available' => false],
                'source_label' => 'LMS',
                'source_message' => 'Snapshot belum tersedia.',
                'last_recorded_at' => null,
                'actual_meta' => [
                    'on_track_students' => 0,
                    'eligible_students' => 0,
                    'average_actual_progress' => null,
                    'average_expected_progress' => null,
                    'excluded_timeline_count' => 0,
                    'excluded_progress_count' => 0,
                ],
            ],
        ];
    }

    private function period(): array
    {
        return [
            'month' => '2026-07',
            'label' => 'Juli 2026',
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
            'actual_date_to' => '2026-07-29',
            'previous_month' => '2026-06',
            'previous_label' => 'Juni 2026',
            'elapsed_percentage' => 93.5,
        ];
    }

    private function fallback(): array
    {
        return [
            'title' => 'Executive Brief — Juli 2026',
            'headline' => '1 KPI membutuhkan tindakan',
            'summary' => 'Revenue tertinggal dan Completion Rate belum tersedia.',
            'root_causes' => [],
            'recommendations' => ['Tinjau KPI Revenue.'],
            'priority' => 'Immediate',
            'is_ai_generated' => false,
        ];
    }
}
