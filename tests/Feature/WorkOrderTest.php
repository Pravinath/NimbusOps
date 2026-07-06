<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Customer;
use App\Models\Technician;
use App\Models\TechnicianAssignment;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_technician_can_view_work_order(): void
    {
        [$technicianUser, $workOrder] = $this->createWorkOrder();

        $this->actingAs($technicianUser, 'sanctum')
            ->getJson("/api/work-orders/{$workOrder->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $workOrder->id);
    }

    public function test_unassigned_technician_cannot_view_work_order(): void
    {
        [, $workOrder] = $this->createWorkOrder();

        $otherTechnician = User::factory()->create([
            'role' => 'technician',
            'status' => 'active',
        ]);

        $this->actingAs($otherTechnician, 'sanctum')
            ->getJson("/api/work-orders/{$workOrder->id}")
            ->assertForbidden();
    }

    public function test_technician_can_complete_work_order_workflow(): void
    {
        [$technicianUser, $workOrder, $technician] =
            $this->createWorkOrder();

        $this->actingAs($technicianUser, 'sanctum')
            ->patchJson("/api/work-orders/{$workOrder->id}/accept", [
                'notes' => 'Job accepted.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted');

        $this->actingAs($technicianUser, 'sanctum')
            ->patchJson("/api/work-orders/{$workOrder->id}/on-the-way", [
                'notes' => 'Travelling to customer.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'on_the_way');

        $this->actingAs($technicianUser, 'sanctum')
            ->patchJson("/api/work-orders/{$workOrder->id}/start", [
                'notes' => 'Inspection started.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'started');

        $this->actingAs($technicianUser, 'sanctum')
            ->patchJson("/api/work-orders/{$workOrder->id}/complete", [
                'resolution_summary' => 'Router cable was replaced.',
                'notes' => 'Internet connection restored.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('complaints', [
            'id' => $workOrder->complaint_id,
            'status' => 'resolved',
        ]);

        $this->assertDatabaseHas('technicians', [
            'id' => $technician->id,
            'current_workload' => 0,
            'availability_status' => 'available',
        ]);

        $this->assertDatabaseCount('work_order_updates', 4);
    }

    public function test_work_order_cannot_skip_status_workflow(): void
    {
        [$technicianUser, $workOrder] = $this->createWorkOrder();

        $this->actingAs($technicianUser, 'sanctum')
            ->patchJson("/api/work-orders/{$workOrder->id}/start")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('work_orders', [
            'id' => $workOrder->id,
            'status' => 'created',
        ]);
    }

    private function createWorkOrder(): array
    {
        $customerUser = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $technicianUser = User::factory()->create([
            'role' => 'technician',
            'status' => 'active',
        ]);

        $dispatcher = User::factory()->create([
            'role' => 'dispatcher',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'address' => '10 Main Street',
            'city' => 'Colombo',
        ]);

        $technician = Technician::create([
            'user_id' => $technicianUser->id,
            'skill_category' => 'network',
            'availability_status' => 'busy',
            'current_workload' => 1,
        ]);

        $complaint = Complaint::create([
            'customer_id' => $customer->id,
            'created_by_user_id' => $customerUser->id,
            'title' => 'Internet not working',
            'description' => 'Internet connection is down.',
            'status' => 'assigned',
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
            'required_skill' => 'network',
            'status' => 'created',
        ]);

        return [$technicianUser, $workOrder, $technician];
    }



        public function test_assigned_technician_can_add_progress_note(): void
        {
            [$technicianUser, $workOrder] = $this->createWorkOrder();

            $this->actingAs($technicianUser, 'sanctum')
                ->postJson("/api/work-orders/{$workOrder->id}/updates", [
                    'notes' => 'Router and network cable inspected.',
                    'metadata' => [
                        'location' => 'customer_site',
                    ],
                ])
                ->assertCreated()
                ->assertJsonPath(
                    'data.notes',
                    'Router and network cable inspected.'
                );

            $this->assertDatabaseHas('work_order_updates', [
                'work_order_id' => $workOrder->id,
                'user_id' => $technicianUser->id,
                'update_type' => 'note_added',
                'notes' => 'Router and network cable inspected.',
            ]);
        }
}