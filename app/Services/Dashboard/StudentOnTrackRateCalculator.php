<?php

namespace App\Services\Dashboard;

use Carbon\CarbonImmutable;
use Throwable;

class StudentOnTrackRateCalculator
{
    public const ON_TRACK_THRESHOLD = 90.0;

    /**
     * @param  array<int, array<string, mixed>>  $students
     * @return array<string, mixed>
     */
    public function calculate(array $students, string $evaluationDate): array
    {
        $evaluation = CarbonImmutable::parse($evaluationDate)->startOfDay();
        $eligible = [];
        $excludedTimeline = 0;
        $excludedProgress = 0;
        $notStarted = 0;
        $finished = 0;
        $inactive = 0;

        foreach ($students as $student) {
            if (($student['is_active'] ?? true) !== true) {
                $inactive++;
                continue;
            }

            $timeline = $this->resolveTimeline(
                $student['batch_start_date'] ?? null,
                $student['batch_end_date'] ?? null,
                $evaluation
            );

            if ($timeline['status'] === 'invalid') {
                $excludedTimeline++;
                continue;
            }

            if ($timeline['status'] === 'not_started') {
                $notStarted++;
                continue;
            }

            if ($timeline['status'] === 'finished') {
                $finished++;
                continue;
            }

            if (! is_numeric($student['actual_progress'] ?? null)) {
                $excludedProgress++;
                continue;
            }

            $actual = $this->clamp((float) $student['actual_progress']);
            $expected = $timeline['expected_progress'];
            $scheduleAchievement = $expected <= 0
                ? 100.0
                : ($actual / $expected) * 100;

            $eligible[] = [
                ...$student,
                'actual_progress' => round($actual, 2),
                'expected_progress' => round($expected, 2),
                'schedule_achievement' => round($scheduleAchievement, 2),
                'is_on_track' => $expected <= 0
                    || $scheduleAchievement >= self::ON_TRACK_THRESHOLD,
            ];
        }

        $eligibleCount = count($eligible);
        $onTrackCount = count(array_filter(
            $eligible,
            fn (array $student): bool => $student['is_on_track']
        ));

        return [
            'has_data' => $eligibleCount > 0,
            'rate' => $eligibleCount > 0
                ? round(($onTrackCount / $eligibleCount) * 100, 2)
                : null,
            'eligible_students' => $eligibleCount,
            'on_track_students' => $onTrackCount,
            'average_actual_progress' => $eligibleCount > 0
                ? round(array_sum(array_column($eligible, 'actual_progress')) / $eligibleCount, 2)
                : null,
            'average_expected_progress' => $eligibleCount > 0
                ? round(array_sum(array_column($eligible, 'expected_progress')) / $eligibleCount, 2)
                : null,
            'on_track_threshold' => self::ON_TRACK_THRESHOLD,
            'excluded_timeline_count' => $excludedTimeline,
            'excluded_progress_count' => $excludedProgress,
            'not_started_count' => $notStarted,
            'finished_count' => $finished,
            'inactive_count' => $inactive,
            'evaluation_date' => $evaluation->toDateString(),
            'students' => $eligible,
        ];
    }

    /**
     * @return array{status: string, expected_progress: float}
     */
    private function resolveTimeline(
        mixed $startDate,
        mixed $endDate,
        CarbonImmutable $evaluation
    ): array {
        try {
            if (! $startDate || ! $endDate) {
                throw new \InvalidArgumentException('Timeline is incomplete.');
            }

            $start = CarbonImmutable::parse($startDate)->startOfDay();
            $end = CarbonImmutable::parse($endDate)->startOfDay();
        } catch (Throwable) {
            return ['status' => 'invalid', 'expected_progress' => 0.0];
        }

        $duration = $start->diffInDays($end, false);

        if ($duration <= 0) {
            return ['status' => 'invalid', 'expected_progress' => 0.0];
        }

        if ($evaluation->lt($start)) {
            return ['status' => 'not_started', 'expected_progress' => 0.0];
        }

        if ($evaluation->gt($end)) {
            return ['status' => 'finished', 'expected_progress' => 100.0];
        }

        $elapsed = $start->diffInDays($evaluation, false);

        return [
            'status' => 'active',
            'expected_progress' => $this->clamp(($elapsed / $duration) * 100),
        ];
    }

    private function clamp(float $value): float
    {
        return max(0.0, min(100.0, $value));
    }
}
