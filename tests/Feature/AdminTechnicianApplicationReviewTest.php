<?php

namespace Tests\Feature;

use App\Models\ServiceArea;
use App\Models\TechnicianApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTechnicianApplicationReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_technician_applications(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $application = $this->createTechnicianApplicationForReview();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/technician-applications')
            ->assertOk()
            ->assertJsonPath('data.0.id', $application->id)
            ->assertJsonPath('data.0.user.email', $application->user->email);
    }

    public function test_non_admin_cannot_review_technician_applications(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/admin/technician-applications')
            ->assertForbidden();
    }

    public function test_admin_can_update_application_review_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $application = $this->createTechnicianApplicationForReview();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/technician-applications/{$application->id}/status", [
                'status' => 'under_review',
                'review_notes' => 'Documents are being checked.',
            ])
            ->assertOk()
            ->assertJsonPath('application.status', 'under_review')
            ->assertJsonPath('application.reviewed_by_user_id', $admin->id);

        $this->assertDatabaseHas('technician_applications', [
            'id' => $application->id,
            'status' => 'under_review',
            'reviewed_by_user_id' => $admin->id,
        ]);
    }

    public function test_rejection_requires_reason(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $application = $this->createTechnicianApplicationForReview();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/technician-applications/{$application->id}/status", [
                'status' => 'rejected',
            ])
            ->assertUnprocessable();
    }

    public function test_admin_can_approve_application_and_activate_technician(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $application = $this->createTechnicianApplicationForReview();

        $this->actingAs($application->user, 'sanctum')
            ->postJson('/api/technician-applications/me/documents', [
                'document_type' => 'identity',
                'document' => UploadedFile::fake()->create('nic.pdf', 100, 'application/pdf'),
            ])
            ->assertCreated();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/technician-applications/{$application->id}/approve")
            ->assertOk()
            ->assertJsonPath('application.status', 'approved')
            ->assertJsonPath('application.user.role', 'technician');

        $this->assertDatabaseHas('users', [
            'id' => $application->user_id,
            'role' => 'technician',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('technicians', [
            'user_id' => $application->user_id,
            'skill_category' => 'electrical',
            'availability_status' => 'available',
        ]);
    }

    public function test_approval_requires_at_least_one_document(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $application = $this->createTechnicianApplicationForReview();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/technician-applications/{$application->id}/approve")
            ->assertUnprocessable();
    }


    public function test_admin_can_view_application_document(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $application = $this->createTechnicianApplicationForReview();

        $this->actingAs($application->user, 'sanctum')
            ->postJson('/api/technician-applications/me/documents', [
                'document_type' => 'identity',
                'document' => UploadedFile::fake()->create('nic.pdf', 100, 'application/pdf'),
            ])
            ->assertCreated();

        $document = $application->documents()->firstOrFail();

        $this->actingAs($admin, 'sanctum')
            ->get("/api/admin/technician-applications/{$application->id}/documents/{$document->id}/view")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_cannot_view_document_from_another_application(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $application = $this->createTechnicianApplicationForReview();

        $this->actingAs($application->user, 'sanctum')
            ->postJson('/api/technician-applications/me/documents', [
                'document_type' => 'identity',
                'document' => UploadedFile::fake()->create('nic.pdf', 100, 'application/pdf'),
            ])
            ->assertCreated();

        $document = $application->documents()->firstOrFail();
        $otherApplication = $this->createTechnicianApplicationForReview();

        $this->actingAs($admin, 'sanctum')
            ->get("/api/admin/technician-applications/{$otherApplication->id}/documents/{$document->id}/view")
            ->assertNotFound();
    }
    private function createTechnicianApplicationForReview(): TechnicianApplication
    {
        $applicant = User::factory()->create([
            'role' => 'technician_applicant',
            'status' => 'active',
        ]);
        $serviceArea = ServiceArea::create([
            'name' => 'Colombo Central',
            'city' => 'Colombo',
            'zone' => 'Zone 1',
            'status' => 'active',
        ]);

        $this->actingAs($applicant, 'sanctum')
            ->postJson('/api/technician-applications', [
                'full_name' => 'Kasun Silva',
                'date_of_birth' => '1995-04-12',
                'phone' => '0771234567',
                'address' => '12 Station Road',
                'city' => 'Colombo',
                'years_experience' => 4,
                'highest_qualification' => 'NVQ Level 4 in Electrical Engineering',
                'skills' => ['electrical', 'ac'],
                'preferred_service_area_id' => $serviceArea->id,
                'motivation' => 'I want to provide reliable technical service to customers in my area.',
            ])
            ->assertCreated();

        return TechnicianApplication::query()
            ->with('user')
            ->where('user_id', $applicant->id)
            ->firstOrFail();
    }
}