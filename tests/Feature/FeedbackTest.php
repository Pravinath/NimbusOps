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

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_feedback_after_completion(): void
    {
        [$customerUser, $complaint, $workOrder, $technician] =
            $this->createServiceRecord('completed');

        $this->actingAs($customerUser, 'sanctum')
            ->postJson('/api/feedback', [
                'complaint_id' => $complaint->id,
                'work_order_id' => $workOrder->id,
                'rating' => 5,
                'comment' => 'Excellent service.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.rating', 5);

        $this->assertDatabaseHas('feedback', [
            'complaint_id' => $complaint->id,
            'technician_id' => $technician->id,
            'rating' => 5,
        ]);

        $this->assertDatabaseHas('technicians', [
            'id' => $technician->id,
            'performance_score' => 5,
        ]);

        $this->assertDatabaseHas('complaint_timelines', [
            'complaint_id' => $complaint->id,
            'event_type' => 'feedback_submitted',
        ]);
    }

    public function test_feedback_is_rejected_before_completion(): void
    {
        [$customerUser, $complaint, $workOrder] =
            $this->createServiceRecord('started');

        $this->actingAs($customerUser, 'sanctum')
            ->postJson('/api/feedback', [
                'complaint_id' => $complaint->id,
                'work_order_id' => $workOrder->id,
                'rating' => 4,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('work_order_id');
    }

    public function test_customer_cannot_submit_feedback_for_another_customer(): void
    {
        [, $complaint, $workOrder] =
            $this->createServiceRecord('completed');

        $otherCustomer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $this->actingAs($otherCustomer, 'sanctum')
            ->postJson('/api/feedback', [
                'complaint_id' => $complaint->id,
                'work_order_id' => $workOrder->id,
                'rating' => 1,
            ])
            ->assertForbidden();
    }

    private function createServiceRecord(string $status): array
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
            'availability_status' => 'available',
            'current_workload' => 0,
        ]);

        $complaint = Complaint::create([
            'customer_id' => $customer->id,
            'created_by_user_id' => $customerUser->id,
            'title' => 'Internet issue',
            'description' => 'Internet connection was unavailable.',
            'status' => $status === 'completed'
                ? 'resolved'
                : 'in_progress',
            'priority' => 'high',
            'resolved_at' => $status === 'completed'
                ? now()
                : null,
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
            'status' => $status,
            'completed_at' => $status === 'completed'
                ? now()
                : null,
        ]);

        return [
            $customerUser,
            $complaint,
            $workOrder,
            $technician,
        ];
    }
}
