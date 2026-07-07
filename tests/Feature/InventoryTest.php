<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Customer;
use App\Models\SparePart;
use App\Models\Technician;
use App\Models\TechnicianAssignment;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_manager_can_create_and_adjust_stock(): void
    {
        $manager = User::factory()->create(['role' => 'inventory']);

        $response = $this->actingAs($manager, 'sanctum')
            ->postJson('/api/spare-parts', [
                'sku' => 'NET-001',
                'name' => 'Network Cable',
                'stock_quantity' => 10,
                'reorder_level' => 3,
                'unit_cost' => 750,
            ])
            ->assertCreated();

        $partId = $response->json('data.id');

        $this->actingAs($manager, 'sanctum')
            ->patchJson("/api/spare-parts/{$partId}/stock", [
                'operation' => 'increase',
                'quantity' => 5,
                'notes' => 'New stock delivery.',
            ])
            ->assertOk()
            ->assertJsonPath('data.stock_quantity', 15);

        $this->assertDatabaseHas('stock_movements', [
            'spare_part_id' => $partId,
            'movement_type' => 'stock_in',
            'quantity_before' => 10,
            'quantity_after' => 15,
        ]);
    }

    public function test_spare_part_usage_reduces_stock(): void
    {
        [$technicianUser, $workOrder] = $this->createStartedWorkOrder();

        $part = SparePart::create([
            'sku' => 'NET-002',
            'name' => 'Router Adapter',
            'stock_quantity' => 5,
            'reorder_level' => 2,
            'unit_cost' => 1500,
            'status' => 'active',
        ]);

        $this->actingAs($technicianUser, 'sanctum')
            ->postJson(
                "/api/work-orders/{$workOrder->id}/use-spare-part",
                [
                    'spare_part_id' => $part->id,
                    'quantity' => 2,
                    'notes' => 'Replaced damaged adapters.',
                ]
            )
            ->assertCreated()
            ->assertJsonPath('data.quantity', 2);

        $this->assertDatabaseHas('spare_parts', [
            'id' => $part->id,
            'stock_quantity' => 3,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'spare_part_id' => $part->id,
            'movement_type' => 'work_order_usage',
            'quantity_before' => 5,
            'quantity_after' => 3,
        ]);
    }

    public function test_usage_is_rejected_when_stock_is_insufficient(): void
    {
        [$technicianUser, $workOrder] = $this->createStartedWorkOrder();

        $part = SparePart::create([
            'sku' => 'NET-003',
            'name' => 'Router',
            'stock_quantity' => 1,
            'reorder_level' => 1,
            'unit_cost' => 5000,
            'status' => 'active',
        ]);

        $this->actingAs($technicianUser, 'sanctum')
            ->postJson(
                "/api/work-orders/{$workOrder->id}/use-spare-part",
                [
                    'spare_part_id' => $part->id,
                    'quantity' => 2,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $this->assertDatabaseHas('spare_parts', [
            'id' => $part->id,
            'stock_quantity' => 1,
        ]);
    }

    public function test_low_stock_endpoint_returns_low_items(): void
    {
        $manager = User::factory()->create(['role' => 'inventory']);

        $lowPart = SparePart::create([
            'sku' => 'LOW-001',
            'name' => 'Low Stock Item',
            'stock_quantity' => 2,
            'reorder_level' => 3,
            'unit_cost' => 100,
        ]);

        SparePart::create([
            'sku' => 'HIGH-001',
            'name' => 'Available Item',
            'stock_quantity' => 20,
            'reorder_level' => 3,
            'unit_cost' => 100,
        ]);

        $this->actingAs($manager, 'sanctum')
            ->getJson('/api/inventory/low-stock')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $lowPart->id);
    }

    private function createStartedWorkOrder(): array
    {
        $customerUser = User::factory()->create(['role' => 'customer']);
        $technicianUser = User::factory()->create(['role' => 'technician']);
        $dispatcher = User::factory()->create(['role' => 'dispatcher']);

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
            'title' => 'Internet problem',
            'description' => 'Internet is unavailable.',
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
            'required_skill' => 'network',
            'status' => 'started',
            'started_at' => now(),
        ]);

        return [$technicianUser, $workOrder];
    }
}
