<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Customer;
use App\Models\Technician;
use App\Models\TechnicianAssignment;
use App\Models\User;
use App\Models\WorkOrder;
use App\Modules\Notification\Services\NotificationService;
use App\Modules\SLA\Services\SlaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_receives_complaint_notification_and_can_read_it(): void
    {
        [$user, $customer] = $this->createCustomer();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/complaints', [
                'customer_id' => $customer->id,
                'title' => 'Internet unavailable',
                'description' => 'The connection is not working.',
            ])
            ->assertCreated();

        $notification = $user->notifications()->firstOrFail();

        $this->assertSame('complaint_created', $notification->data['event_type']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('data.data.0.data.event_type', 'complaint_created');

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/notifications/{$notification->id}/read")
            ->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_assignment_notifies_assigned_technician(): void
    {
        [$customerUser, $customer] = $this->createCustomer();
        $dispatcher = User::factory()->create(['role' => 'dispatcher']);
        $technicianUser = User::factory()->create(['role' => 'technician']);

        $technician = Technician::create([
            'user_id' => $technicianUser->id,
            'skill_category' => 'network',
            'availability_status' => 'available',
        ]);

        $complaint = Complaint::create([
            'customer_id' => $customer->id,
            'created_by_user_id' => $customerUser->id,
            'title' => 'Internet unavailable',
            'description' => 'The connection is down.',
            'status' => 'classified',
            'priority' => 'high',
        ]);

        $this->actingAs($dispatcher, 'sanctum')
            ->postJson("/api/complaints/{$complaint->id}/assign-technician", [
                'technician_id' => $technician->id,
            ])
            ->assertCreated();

        $this->assertSame(
            'technician_assigned',
            $technicianUser->notifications()->firstOrFail()->data['event_type']
        );
    }

    public function test_sla_breach_notifies_supervisor(): void
    {
        [$customerUser, $customer] = $this->createCustomer();
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $complaint = Complaint::create([
            'customer_id' => $customer->id,
            'created_by_user_id' => $customerUser->id,
            'title' => 'Critical outage',
            'description' => 'All service is unavailable.',
            'status' => 'new',
            'priority' => 'critical',
            'sla_due_at' => now()->subMinute(),
        ]);

        app(SlaService::class)->detectBreaches();

        $this->assertSame(
            'sla_breached',
            $supervisor->notifications()->firstOrFail()->data['event_type']
        );
    }

    public function test_low_stock_notifies_inventory_manager(): void
    {
        $manager = User::factory()->create(['role' => 'inventory']);

        $this->actingAs($manager, 'sanctum')
            ->postJson('/api/spare-parts', [
                'sku' => 'LOW-100',
                'name' => 'Router Adapter',
                'stock_quantity' => 2,
                'reorder_level' => 2,
                'unit_cost' => 1500,
            ])
            ->assertCreated();

        $this->assertSame(
            'low_stock',
            $manager->notifications()->firstOrFail()->data['event_type']
        );
    }

    public function test_resolution_notifies_customer(): void
    {
        [$customerUser, $customer] = $this->createCustomer();
        $technicianUser = User::factory()->create(['role' => 'technician']);
        $dispatcher = User::factory()->create(['role' => 'dispatcher']);

        $technician = Technician::create([
            'user_id' => $technicianUser->id,
            'skill_category' => 'network',
            'availability_status' => 'busy',
            'current_workload' => 1,
        ]);

        $complaint = Complaint::create([
            'customer_id' => $customer->id,
            'created_by_user_id' => $customerUser->id,
            'title' => 'Internet unavailable',
            'description' => 'The connection is down.',
            'status' => 'in_progress',
            'priority' => 'high',
        ]);

        $assignment = TechnicianAssignment::create([
            'complaint_id' => $complaint->id,
            'technician_id' => $technician->id,
            'assigned_by_user_id' => $dispatcher->id,
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        $workOrder = WorkOrder::create([
            'complaint_id' => $complaint->id,
            'technician_assignment_id' => $assignment->id,
            'technician_id' => $technician->id,
            'status' => 'started',
        ]);

        app(NotificationService::class)->complaintResolved($workOrder);

        $this->assertSame(
            'complaint_resolved',
            $customerUser->notifications()->firstOrFail()->data['event_type']
        );
    }

    private function createCustomer(): array
    {
        $user = User::factory()->create(['role' => 'customer']);

        $customer = Customer::create([
            'user_id' => $user->id,
            'address' => '10 Main Street',
            'city' => 'Colombo',
        ]);

        return [$user, $customer];
    }
}
