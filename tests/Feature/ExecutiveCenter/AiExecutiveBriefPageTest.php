<?php

namespace Tests\Feature\ExecutiveCenter;

use App\Models\User;
use App\Services\Dashboard\ExecutiveDashboardService;
use Mockery;
use Tests\TestCase;

class AiExecutiveBriefPageTest extends TestCase
{
    public function test_authorized_user_can_open_brief_for_valid_period(): void
    {
        $service = Mockery::mock(ExecutiveDashboardService::class);
        $service->shouldReceive('getData')->once()->with(['month' => '2026-07'])->andReturn($this->payload());
        $this->app->instance(ExecutiveDashboardService::class, $service);

        $response = $this->actingAs($this->user('admin'))
            ->get('/executive-center/ai-executive-brief?period=2026-07');

        $response->assertOk()
            ->assertSee('AI Executive Brief')
            ->assertSee('Revenue perlu tindakan')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_unauthorized_role_is_rejected(): void
    {
        $this->actingAs($this->user('sales'))
            ->get('/executive-center/ai-executive-brief?period=2026-07')
            ->assertForbidden();
    }

    public function test_invalid_period_is_rejected(): void
    {
        $this->actingAs($this->user('admin'))
            ->get('/executive-center/ai-executive-brief?period=July-2026')
            ->assertSessionHasErrors('period');
    }

    private function user(string $role): User
    {
        $user = new User;
        $user->forceFill([
            'id' => 999,
            'name' => 'Executive Test',
            'email' => 'executive@example.test',
            'role' => $role,
            'email_verified_at' => now(),
        ]);

        return $user;
    }

    private function payload(): array
    {
        return [
            'period' => ['month' => '2026-07', 'label' => 'Juli 2026'],
            'executiveBrief' => [
                'headline' => 'Revenue perlu tindakan',
                'executive_summary' => '<script>alert(1)</script>',
                'root_causes' => [],
                'risk_opportunity' => [],
                'recommended_decisions' => [],
                'confidence' => ['label' => 'Moderate', 'score' => 70, 'source_count' => 4],
                'is_ai_generated' => true,
            ],
        ];
    }
}
