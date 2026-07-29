<?php

namespace Tests\Unit;

use App\Services\Dashboard\StudentOnTrackRateCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StudentOnTrackRateCalculatorTest extends TestCase
{
    private StudentOnTrackRateCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new StudentOnTrackRateCalculator();
    }

    #[DataProvider('scheduleCases')]
    public function test_schedule_achievement_classification(
        float $actualProgress,
        bool $expectedOnTrack
    ): void {
        $result = $this->calculator->calculate([
            $this->student('2026-07-01', '2026-07-21', $actualProgress),
        ], '2026-07-11');

        $this->assertSame(50.0, $result['students'][0]['expected_progress']);
        $this->assertSame($expectedOnTrack, $result['students'][0]['is_on_track']);
    }

    public static function scheduleCases(): array
    {
        return [
            '45 percent at 50 percent timeline is on track' => [45, true],
            '30 percent at 50 percent timeline is behind' => [30, false],
        ];
    }

    public function test_two_of_three_eligible_students_produce_66_67_percent(): void
    {
        $result = $this->calculator->calculate([
            $this->student('2026-07-01', '2026-07-21', 45),
            $this->student('2026-07-01', '2026-07-21', 50),
            $this->student('2026-07-01', '2026-07-21', 30),
        ], '2026-07-11');

        $this->assertSame(3, $result['eligible_students']);
        $this->assertSame(2, $result['on_track_students']);
        $this->assertSame(66.67, $result['rate']);
    }

    public function test_not_started_finished_and_invalid_batches_are_not_eligible(): void
    {
        $result = $this->calculator->calculate([
            $this->student('2026-08-01', '2026-08-31', 0),
            $this->student('2026-06-01', '2026-06-30', 100),
            $this->student(null, '2026-07-31', 50),
            $this->student('2026-07-31', '2026-07-01', 50),
        ], '2026-07-15');

        $this->assertFalse($result['has_data']);
        $this->assertNull($result['rate']);
        $this->assertSame(1, $result['not_started_count']);
        $this->assertSame(1, $result['finished_count']);
        $this->assertSame(2, $result['excluded_timeline_count']);
    }

    public function test_missing_progress_is_excluded_instead_of_assumed_zero(): void
    {
        $result = $this->calculator->calculate([
            $this->student('2026-07-01', '2026-07-31', null),
        ], '2026-07-15');

        $this->assertFalse($result['has_data']);
        $this->assertSame(1, $result['excluded_progress_count']);
    }

    public function test_zero_expected_progress_does_not_divide_by_zero_or_mark_student_behind(): void
    {
        $result = $this->calculator->calculate([
            $this->student('2026-07-01', '2026-07-31', 0),
        ], '2026-07-01');

        $this->assertSame(100.0, $result['students'][0]['schedule_achievement']);
        $this->assertTrue($result['students'][0]['is_on_track']);
        $this->assertSame(100.0, $result['rate']);
    }

    public function test_inactive_or_cancelled_students_are_not_counted(): void
    {
        $active = $this->student('2026-07-01', '2026-07-31', 50);
        $inactive = [
            ...$this->student('2026-07-01', '2026-07-31', 50),
            'is_active' => false,
        ];

        $result = $this->calculator->calculate([$active, $inactive], '2026-07-15');

        $this->assertSame(1, $result['eligible_students']);
        $this->assertSame(1, $result['inactive_count']);
    }

    private function student(
        ?string $startDate,
        ?string $endDate,
        ?float $actualProgress
    ): array {
        return [
            'batch_start_date' => $startDate,
            'batch_end_date' => $endDate,
            'actual_progress' => $actualProgress,
        ];
    }
}
