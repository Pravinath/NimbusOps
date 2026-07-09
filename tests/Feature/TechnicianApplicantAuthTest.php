<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicianApplicantAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_person_can_create_restricted_technician_applicant_account(): void
    {
        $response = $this->postJson('/api/auth/technician/register', [
            'name' => 'Ruwan Jayasinghe',
            'email' => 'ruwan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'technician',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.role', 'technician_applicant')
            ->assertJsonStructure(['message', 'user', 'token']);

        $this->assertDatabaseHas('users', [
            'email' => 'ruwan@example.com',
            'role' => 'technician_applicant',
            'auth_provider' => 'password',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'ruwan@example.com',
            'role' => 'technician',
        ]);
    }

    public function test_technician_applicant_registration_requires_password_confirmation(): void
    {
        $this->postJson('/api/auth/technician/register', [
            'name' => 'Ruwan Jayasinghe',
            'email' => 'ruwan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }
}
