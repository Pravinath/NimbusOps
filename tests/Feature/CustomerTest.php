<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_customer(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $customerUser = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/customers', [
                'user_id' => $customerUser->id,
                'phone' => '0771234567',
                'address' => '10 Main Street',
                'city' => 'Colombo',
                'status' => 'active',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.user_id', $customerUser->id);

        $this->assertDatabaseHas('customers', [
            'user_id' => $customerUser->id,
            'city' => 'Colombo',
        ]);
    }

    public function test_guest_cannot_view_customers(): void
    {
        $this->getJson('/api/customers')->assertUnauthorized();
    }

    public function test_customer_user_id_must_be_unique(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $customerUser = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $data = [
            'user_id' => $customerUser->id,
            'address' => '10 Main Street',
            'city' => 'Colombo',
        ];

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/customers', $data)
            ->assertCreated();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/customers', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');
    }
}