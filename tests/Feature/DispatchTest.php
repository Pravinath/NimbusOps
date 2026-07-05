<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Customer;
use App\Models\ServiceArea;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_best_matching_technician_is_ranked_first(): void
    {
        $dispatcher = User::factory()->create(['role' => 'dispatcher']);
        [$complaint, $area] = $this->createClassifiedComplaint();

        $best = $this->createTechnician($area->id, 'network', 'available', 0);
        $this->createTechnician($area->id, 'general', 'available', 3);

        $response = $this->actingAs($dispatcher, 'sanctum')
            ->getJson(
                "/api/complaints/{$complaint->id}/suggest-technicians"
            );

        $response->assertOk()
            ->assertJsonPath('data.0.technician.id', $best->id)
            ->assertJsonPath('data.0.assignable', true);
    }

    public function test_dispatcher_can_assign_available_technician(): void
    {
        $dispatcher = User::factory()->create(['role' => 'dispatcher']);
        [$complaint, $area] = $this->createClassifiedComplaint();

        $technician = $this->createTechnician(
            $area->id,
            'network',
            'available',
            0
        );

        $this->actingAs($dispatcher, 'sanctum')
            ->postJson(
                "/api/complaints/{$complaint->id}/assign-technician",
                ['technician_id' => $technician->id]
            )
            ->assertCreated()
            ->assertJsonPath('data.technician_id', $technician->id)
            ->assertJsonPath('data.work_order.status', 'created');

        $this->assertDatabaseHas('work_orders', [
            'complaint_id' => $complaint->id,
            'technician_id' => $technician->id,
            'status' => 'created',
        ]);

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'assigned',
        ]);

        $this->assertDatabaseHas('technicians', [
            'id' => $technician->id,
            'availability_status' => 'busy',
            'current_workload' => 1,
        ]);

        $this->assertDatabaseHas('complaint_timelines', [
            'complaint_id' => $complaint->id,
            'event_type' => 'technician_assigned',
        ]);
    }

    public function test_dispatcher_cannot_assign_unavailable_technician(): void
    {
        $dispatcher = User::factory()->create(['role' => 'dispatcher']);
        [$complaint, $area] = $this->createClassifiedComplaint();

        $technician = $this->createTechnician(
            $area->id,
            'network',
            'offline',
            0
        );

        $this->actingAs($dispatcher, 'sanctum')
            ->postJson(
                "/api/complaints/{$complaint->id}/assign-technician",
                ['technician_id' => $technician->id]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('technician_id');

        $this->assertDatabaseMissing('work_orders', [
            'complaint_id' => $complaint->id,
        ]);
    }

    public function test_admin_can_override_unavailable_technician(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$complaint, $area] = $this->createClassifiedComplaint();

        $technician = $this->createTechnician(
            $area->id,
            'network',
            'offline',
            0
        );

        $this->actingAs($admin, 'sanctum')
            ->postJson(
                "/api/complaints/{$complaint->id}/assign-technician",
                [
                    'technician_id' => $technician->id,
                    'override' => true,
                ]
            )
            ->assertCreated()
            ->assertJsonPath('data.is_override', true);
    }

    private function createClassifiedComplaint(): array
    {
        $customerUser = User::factory()->create(['role' => 'customer']);

        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'address' => '10 Main Street',
            'city' => 'Colombo',
        ]);

        $area = ServiceArea::create([
            'name' => 'Colombo Central',
            'city' => 'Colombo',
            'zone' => 'Zone 1',
        ]);

        $complaint = Complaint::create([
            'customer_id' => $customer->id,
            'service_area_id' => $area->id,
            'created_by_user_id' => $customerUser->id,
            'title' => 'Internet not working',
            'description' => 'Internet connection is completely down.',
            'status' => 'classified',
            'priority' => 'high',
        ]);

        $complaint->aiClassification()->create([
            'provider' => 'mock',
            'issue_category' => 'internet_connectivity',
            'predicted_priority' => 'high',
            'suggested_skill' => 'network',
            'suggested_spare_parts' => ['network cable'],
            'suggested_sla_minutes' => 240,
            'repeated_complaint_risk' => false,
            'summary' => 'Internet connection is down.',
            'confidence_score' => 88,
            'raw_response' => [],
            'classified_at' => now(),
        ]);

        return [$complaint, $area];
    }

    private function createTechnician(
        int $areaId,
        string $skill,
        string $availability,
        int $workload
    ): Technician {
        $user = User::factory()->create(['role' => 'technician']);

        return Technician::create([
            'user_id' => $user->id,
            'service_area_id' => $areaId,
            'skill_category' => $skill,
            'availability_status' => $availability,
            'current_workload' => $workload,
        ]);
    }
}