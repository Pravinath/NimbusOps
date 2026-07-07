<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Customer;
use App\Models\SlaPolicy;
use App\Models\User;
use App\Modules\SLA\Services\SlaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SlaTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_critical_complaint_gets_two_hour_deadline(): void
    {
        Carbon::setTestNow('2026-07-06 10:00:00');

        SlaPolicy::create([
            'priority' => 'critical',
            'resolution_minutes' => 120,
            'is_active' => true,
        ]);

        $complaint = $this->createComplaint('critical');

        app(SlaService::class)->assignDeadline($complaint);

        $this->assertEquals(
            '2026-07-06 12:00:00',
            $complaint->fresh()->sla_due_at->format('Y-m-d H:i:s')
        );
    }

    public function test_overdue_complaint_is_marked_breached(): void
    {
        Carbon::setTestNow('2026-07-06 12:00:00');

        $complaint = $this->createComplaint('high');

        $complaint->update([
            'sla_due_at' => now()->subMinute(),
        ]);

        $count = app(SlaService::class)->detectBreaches();

        $this->assertSame(1, $count);

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'is_sla_breached' => true,
        ]);

        $this->assertDatabaseHas('complaint_timelines', [
            'complaint_id' => $complaint->id,
            'event_type' => 'sla_breached',
        ]);
    }

    public function test_resolved_complaint_is_not_marked_breached(): void
    {
        Carbon::setTestNow('2026-07-06 12:00:00');

        $complaint = $this->createComplaint('high');

        $complaint->update([
            'status' => 'resolved',
            'sla_due_at' => now()->subMinute(),
            'resolved_at' => now()->subMinutes(10),
        ]);

        $count = app(SlaService::class)->detectBreaches();

        $this->assertSame(0, $count);

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'is_sla_breached' => false,
        ]);
    }

    public function test_sla_command_detects_breaches(): void
    {
        Carbon::setTestNow('2026-07-06 12:00:00');

        $complaint = $this->createComplaint('medium');

        $complaint->update([
            'sla_due_at' => now()->subMinute(),
        ]);

        $this->artisan('sla:check')
            ->expectsOutput(
                'SLA check completed. 1 breach(es) detected.'
            )
            ->assertSuccessful();
    }

    private function createComplaint(string $priority): Complaint
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'address' => '10 Main Street',
            'city' => 'Colombo',
        ]);

        return Complaint::create([
            'customer_id' => $customer->id,
            'created_by_user_id' => $user->id,
            'title' => 'Service problem',
            'description' => 'The service is not working.',
            'status' => 'new',
            'priority' => $priority,
        ]);
    }
}
