<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_own_complaint(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'address' => '10 Main Street',
            'city' => 'Colombo',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/complaints', [
                'customer_id' => $customer->id,
                'title' => 'Internet not working',
                'description' => 'Router red light is blinking.',
                'priority' => 'high',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'new')
            ->assertJsonPath('data.priority', 'high');

        $this->assertDatabaseHas('complaint_timelines', [
            'event_type' => 'complaint_created',
            'to_status' => 'new',
        ]);
    }

    public function test_customer_cannot_create_complaint_for_another_customer(): void
    {
        $firstUser = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $secondUser = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $secondCustomer = Customer::create([
            'user_id' => $secondUser->id,
            'address' => '20 Main Street',
            'city' => 'Colombo',
            'status' => 'active',
        ]);

        $this->actingAs($firstUser, 'sanctum')
            ->postJson('/api/complaints', [
                'customer_id' => $secondCustomer->id,
                'title' => 'Invalid complaint',
                'description' => 'This belongs to another customer.',
            ])
            ->assertForbidden();
    }

    public function test_agent_can_update_complaint_status(): void
    {
        $customerUser = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $agent = User::factory()->create([
            'role' => 'agent',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'address' => '10 Main Street',
            'city' => 'Colombo',
            'status' => 'active',
        ]);

        $complaint = Complaint::create([
            'customer_id' => $customer->id,
            'created_by_user_id' => $customerUser->id,
            'title' => 'Internet not working',
            'description' => 'Router red light is blinking.',
            'status' => 'new',
            'priority' => 'high',
        ]);

        $this->actingAs($agent, 'sanctum')
            ->patchJson("/api/complaints/{$complaint->id}/status", [
                'status' => 'classified',
                'notes' => 'Complaint reviewed by agent.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'classified');

        $this->assertDatabaseHas('complaint_timelines', [
            'complaint_id' => $complaint->id,
            'from_status' => 'new',
            'to_status' => 'classified',
        ]);
    }

    public function test_customer_cannot_view_another_customers_complaint(): void
    {
        $firstUser = User::factory()->create(['role' => 'customer']);
        $secondUser = User::factory()->create(['role' => 'customer']);

        $secondCustomer = Customer::create([
            'user_id' => $secondUser->id,
            'address' => '20 Main Street',
            'city' => 'Colombo',
        ]);

        $complaint = Complaint::create([
            'customer_id' => $secondCustomer->id,
            'created_by_user_id' => $secondUser->id,
            'title' => 'Private complaint',
            'description' => 'Only the owner should see this.',
        ]);

        $this->actingAs($firstUser, 'sanctum')
            ->getJson("/api/complaints/{$complaint->id}")
            ->assertForbidden();
    }

    public function test_complaint_cannot_skip_status_workflow(): void
    {
        $customerUser = User::factory()->create([
            'role' => 'customer',
        ]);

        $agent = User::factory()->create([
            'role' => 'agent',
        ]);

        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'address' => '10 Main Street',
            'city' => 'Colombo',
        ]);

        $complaint = Complaint::create([
            'customer_id' => $customer->id,
            'created_by_user_id' => $customerUser->id,
            'title' => 'Internet issue',
            'description' => 'Connection is unavailable.',
            'status' => 'new',
            'priority' => 'high',
        ]);

        $this->actingAs($agent, 'sanctum')
            ->patchJson("/api/complaints/{$complaint->id}/status", [
                'status' => 'closed',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'new',
        ]);
    }
}
