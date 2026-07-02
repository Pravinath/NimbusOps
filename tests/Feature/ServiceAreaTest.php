<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_service_area(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/service-areas', [
                'name' => 'Colombo Central',
                'city' => 'Colombo',
                'zone' => 'Zone 1',
                'status' => 'active',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Colombo Central');

        $this->assertDatabaseHas('service_areas', [
            'name' => 'Colombo Central',
            'city' => 'Colombo',
        ]);
    }

    public function test_guest_cannot_view_service_areas(): void
    {
        $this->getJson('/api/service-areas')->assertUnauthorized();
    }
}