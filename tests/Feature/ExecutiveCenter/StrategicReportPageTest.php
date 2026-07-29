<?php

namespace Tests\Feature\ExecutiveCenter;

use App\Models\User;
use App\Services\StrategicReports\StrategicReportService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

class StrategicReportPageTest extends TestCase
{
    public function test_authorized_user_can_open_report_library(): void
    {
        $service = Mockery::mock(StrategicReportService::class);
        $service->shouldReceive('library')->once()->andReturn(['reports' => new LengthAwarePaginator([], 0, 12), 'filters' => [], 'years' => collect()]);
        $this->app->instance(StrategicReportService::class, $service);
        $this->actingAs($this->user('admin'))->get('/executive-center/strategic-reports')->assertOk()->assertSee('Strategic Reports')->assertSee('No strategic reports yet');
    }

    public function test_unauthorized_user_cannot_open_report_library(): void
    {
        $this->actingAs($this->user('sales'))->get('/executive-center/strategic-reports')->assertForbidden();
    }

    public function test_invalid_library_filters_are_rejected(): void
    {
        $this->actingAs($this->user('admin'))->get('/executive-center/strategic-reports?type=weekly&status=published&year=1900')->assertSessionHasErrors(['type', 'status', 'year']);
    }

    private function user(string $role): User
    {
        return (new User)->forceFill(['id' => 996, 'name' => 'Test', 'email' => 'reports@example.test', 'role' => $role, 'email_verified_at' => now()]);
    }
}
