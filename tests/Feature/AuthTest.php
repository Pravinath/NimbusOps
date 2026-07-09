<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_with_a_complete_profile(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Nimal Perera',
            'email' => 'nimal@example.com',
            'phone' => '0771234567',
            'address' => '24 Lake Road',
            'city' => 'Colombo',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.role', 'customer')
            ->assertJsonPath('user.customer.city', 'Colombo')
            ->assertJsonStructure(['message', 'user', 'token']);

        $this->assertDatabaseHas('users', [
            'email' => 'nimal@example.com',
            'role' => 'customer',
        ]);

        $this->assertDatabaseHas('customers', [
            'phone' => '0771234567',
            'city' => 'Colombo',
        ]);
    }

    public function test_public_registration_cannot_select_a_privileged_role(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Unsafe Admin',
            'email' => 'unsafe@example.com',
            'phone' => '0777654321',
            'address' => '10 Main Street',
            'city' => 'Kandy',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.role', 'customer');

        $this->assertDatabaseMissing('users', [
            'email' => 'unsafe@example.com',
            'role' => 'admin',
        ]);
    }

    public function test_user_can_login_and_receive_token(): void
    {
        User::factory()->create([
            'email' => 'admin@nimbusops.test',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@nimbusops.test',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'user',
                'token',
            ]);
    }

    public function test_protected_route_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_authenticated_user_can_access_me_route(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }
}
