<?php

namespace Tests\Feature\ExecutiveCenter;

use App\Models\User;
use App\Services\Dashboard\ExecutiveBusinessAttentionService;
use Mockery;
use Tests\TestCase;

class BusinessAttentionPageTest extends TestCase
{
    public function test_authorized_user_can_open_page_and_async_data(): void
    {
        $service = Mockery::mock(ExecutiveBusinessAttentionService::class);
        $service->shouldReceive('getData')->twice()->andReturn($this->payload());
        $this->app->instance(ExecutiveBusinessAttentionService::class, $service);
        $this->actingAs($this->user('admin'))->get('/executive-center/business-attention?period=2026-07')->assertOk()->assertSee('Business Attention')->assertDontSee('Mark as reviewed');
        $this->getJson('/executive-center/business-attention/data?period=2026-07')->assertOk()->assertJsonPath('success', true);
    }

    public function test_unauthorized_user_is_rejected(): void
    {
        $this->actingAs($this->user('sales'))->get('/executive-center/business-attention')->assertForbidden();
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->actingAs($this->user('admin'))->get('/executive-center/business-attention?period=bad&division=bad&state=review')->assertSessionHasErrors(['period', 'division', 'state']);
    }

    private function user(string $role): User
    {
        return (new User)->forceFill(['id' => 997, 'name' => 'Test', 'email' => 'attention@example.test', 'role' => $role, 'email_verified_at' => now()]);
    }

    private function payload(): array
    {
        return ['filters' => ['period' => '2026-07', 'division' => 'all', 'state' => 'open', 'issue' => null], 'period' => ['month' => '2026-07', 'label' => 'Juli 2026'], 'divisions' => [['key' => 'all', 'label' => 'All divisions']], 'states' => [['key' => 'open', 'label' => 'Open issues']], 'issues' => [], 'selectedIssue' => null, 'summary' => ['critical' => 0, 'high' => 0, 'medium' => 0, 'resolved' => 0], 'hasAvailableKpi' => true];
    }
}
