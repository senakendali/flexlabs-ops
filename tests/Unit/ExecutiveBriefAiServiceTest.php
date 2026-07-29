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
                $this->assertStringContainsString('Completion Rate', $prompt);
                $this->assertStringContainsString('"status": "unavailable"', $prompt);
                $this->assertStringContainsString('"previous_period"', $prompt);

                return true;
            })
            ->andReturn([
                'headline' => 'Revenue perlu tindakan segera',
                'summary' => 'Revenue masih tertinggal, sedangkan Completion Rate belum dapat dinilai.',
                'possible_causes' => [
                    'Indikasi awal terlihat dari penurunan revenue dibanding periode sebelumnya.',
                ],
                'recommended_actions' => [
                    'Tetapkan PIC untuk menutup gap KPI Revenue minggu ini.',
                ],
                'priority' => 'Immediate',
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
        $this->assertSame('Immediate', $second['priority']);
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
                'name' => 'Completion Rate',
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
