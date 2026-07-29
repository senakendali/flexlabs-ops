<?php

namespace Tests\Feature\ExecutiveCenter;

use App\Models\User;
use App\Services\Dashboard\ExecutiveKpiScorecardService;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class KpiScorecardPageTest extends TestCase
{
    public function test_authorized_user_can_open_scorecard_with_filters_and_escaped_output(): void
    {
        $service = Mockery::mock(ExecutiveKpiScorecardService::class);
        $service->shouldReceive('getData')->once()->with('2026-07', 'growth')->andReturn($this->payload());
        $this->app->instance(ExecutiveKpiScorecardService::class, $service);

        $this->actingAs($this->user('admin'))->get('/executive-center/kpi-scorecard?period=2026-07&division=growth')
            ->assertOk()->assertSee('KPI Scorecard')->assertSee('&lt;script&gt;bad&lt;/script&gt;', false);
    }

    public function test_unauthorized_user_is_rejected(): void
    {
        $this->actingAs($this->user('sales'))->get('/executive-center/kpi-scorecard')->assertForbidden();
    }

    public function test_default_period_uses_current_month_and_company(): void
    {
        Carbon::setTestNow('2026-07-29 10:00:00');
        $service = Mockery::mock(ExecutiveKpiScorecardService::class);
        $service->shouldReceive('getData')->once()->with('2026-07', 'company')->andReturn($this->payload());
        $this->app->instance(ExecutiveKpiScorecardService::class, $service);

        $this->actingAs($this->user('admin'))->get('/executive-center/kpi-scorecard')->assertOk();
        Carbon::setTestNow();
    }

    public function test_invalid_period_and_division_are_rejected(): void
    {
        $this->actingAs($this->user('admin'))->get('/executive-center/kpi-scorecard?period=July&division=other')
            ->assertSessionHasErrors(['period', 'division']);
    }

    private function user(string $role): User
    {
        return (new User)->forceFill(['id' => 998, 'name' => 'Test', 'email' => 'score@example.test', 'role' => $role, 'email_verified_at' => now()]);
    }

    private function payload(): array
    {
        return [
            'filters' => ['period' => '2026-07', 'division' => 'growth'],
            'period' => ['month' => '2026-07', 'label' => 'Juli 2026'],
            'divisions' => [['key' => 'growth', 'label' => 'Growth', 'count' => 1]],
            'divisionLabel' => 'Growth',
            'scorecardSummary' => ['average_achievement' => 90.0, 'scoreable_count' => 1, 'achieved_count' => 0, 'achieved_percentage' => 0.0, 'attention_count' => 1, 'attention_percentage' => 100.0, 'critical_count' => 0, 'critical_percentage' => 0.0, 'unavailable_count' => 0],
            'scorecard' => [[
                'label' => '<script>bad</script>', 'description' => 'Safe', 'owner' => 'Sales', 'target_formatted' => '100', 'actual_formatted' => '90',
                'achievement_percentage' => 90.0, 'progress_width' => 90.0, 'status' => 'watch', 'status_label' => 'Watch', 'status_reason' => 'Reason',
                'scoreable' => true, 'trend_display' => ['display' => '↑ 2%', 'tone' => 'positive'],
            ]],
            'statusLegend' => 'Shared rule',
        ];
    }
}
