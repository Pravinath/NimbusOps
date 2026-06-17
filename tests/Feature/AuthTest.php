<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

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