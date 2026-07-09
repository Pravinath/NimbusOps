<?php

namespace Tests\Feature;

use App\Models\ServiceArea;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicianTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_technician(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $technicianUser = User::factory()->create([
            'role' => 'technician',
            'status' => 'active',
        ]);

        $serviceArea = ServiceArea::create([
            'name' => 'Colombo Central',
            'city' => 'Colombo',
            'zone' => 'Zone 1',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/technicians', [
                'user_id' => $technicianUser->id,
                'service_area_id' => $serviceArea->id,
                'skill_category' => 'network',
                'availability_status' => 'available',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.user_id', $technicianUser->id)
            ->assertJsonPath('data.skill_category', 'network');

        $this->assertDatabaseHas('technicians', [
            'user_id' => $technicianUser->id,
            'service_area_id' => $serviceArea->id,
        ]);
    }

    public function test_technician_availability_can_be_updated(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $technicianUser = User::factory()->create([
            'role' => 'technician',
            'status' => 'active',
        ]);

        $technician = Technician::create([
            'user_id' => $technicianUser->id,
            'skill_category' => 'electrical',
            'availability_status' => 'available',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/technicians/{$technician->id}/availability", [
                'availability_status' => 'busy',
            ])
            ->assertOk()
            ->assertJsonPath('data.availability_status', 'busy');

        $this->assertDatabaseHas('technicians', [
            'id' => $technician->id,
            'availability_status' => 'busy',
        ]);
    }


    public function test_technician_can_update_own_availability(): void
    {
        $technicianUser = User::factory()->create([
            'role' => 'technician',
            'status' => 'active',
        ]);

        $technician = Technician::create([
            'user_id' => $technicianUser->id,
            'skill_category' => 'electrical',
            'availability_status' => 'available',
        ]);

        $this->actingAs($technicianUser, 'sanctum')
            ->patchJson("/api/technicians/{$technician->id}/availability", [
                'availability_status' => 'on_leave',
            ])
            ->assertOk()
            ->assertJsonPath('data.availability_status', 'on_leave');

        $this->assertDatabaseHas('technicians', [
            'id' => $technician->id,
            'availability_status' => 'on_leave',
        ]);
    }

    public function test_technician_cannot_update_another_technicians_availability(): void
    {
        $technicianUser = User::factory()->create([
            'role' => 'technician',
            'status' => 'active',
        ]);
        $otherTechnicianUser = User::factory()->create([
            'role' => 'technician',
            'status' => 'active',
        ]);

        Technician::create([
            'user_id' => $technicianUser->id,
            'skill_category' => 'network',
            'availability_status' => 'available',
        ]);
        $otherTechnician = Technician::create([
            'user_id' => $otherTechnicianUser->id,
            'skill_category' => 'electrical',
            'availability_status' => 'available',
        ]);

        $this->actingAs($technicianUser, 'sanctum')
            ->patchJson("/api/technicians/{$otherTechnician->id}/availability", [
                'availability_status' => 'busy',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('technicians', [
            'id' => $otherTechnician->id,
            'availability_status' => 'available',
        ]);
    }
    public function test_invalid_skill_category_is_rejected(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $technicianUser = User::factory()->create([
            'role' => 'technician',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/technicians', [
                'user_id' => $technicianUser->id,
                'skill_category' => 'invalid-skill',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('skill_category');
    }

    public function test_guest_cannot_view_technicians(): void
    {
        $this->getJson('/api/technicians')->assertUnauthorized();
    }

    public function test_customer_cannot_create_technician(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $technicianUser = User::factory()->create([
            'role' => 'technician',
            'status' => 'active',
        ]);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/technicians', [
                'user_id' => $technicianUser->id,
                'skill_category' => 'network',
            ])
            ->assertForbidden();
    }
}
