<?php

namespace Tests\Feature;

use App\Models\ServiceArea;
use App\Models\TechnicianApplicationDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TechnicianApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_person_can_submit_a_technician_application(): void
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

        $response = $this->actingAs($applicant, 'sanctum')
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
            ]);

        $response->assertCreated()
            ->assertJsonPath('application.status', 'submitted')
            ->assertJsonStructure([
                'message',
                'application' => ['application_reference'],
            ]);

        $this->assertDatabaseHas('technician_applications', [
            'phone' => '0771234567',
            'status' => 'submitted',
        ]);

        $this->assertDatabaseMissing('technicians', [
            'user_id' => $applicant->id,
        ]);
    }

    public function test_applicant_can_view_their_own_application(): void
    {
        $applicant = User::factory()->create([
            'role' => 'technician_applicant',
            'status' => 'active',
        ]);

        $this->actingAs($applicant, 'sanctum')
            ->postJson('/api/technician-applications', [
                'full_name' => 'Amal Fernando',
                'date_of_birth' => '1998-08-21',
                'phone' => '0712345678',
                'address' => '8 Temple Lane',
                'city' => 'Kandy',
                'years_experience' => 2,
                'highest_qualification' => 'Diploma in Network Engineering',
                'skills' => ['network'],
                'motivation' => 'I enjoy solving network problems and supporting customers professionally.',
            ])->assertCreated();

        $this->actingAs($applicant, 'sanctum')
            ->getJson('/api/technician-applications/me')
            ->assertOk()
            ->assertJsonPath('data.user_id', $applicant->id);
    }

    public function test_customer_cannot_access_technician_application_status(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/technician-applications/me')
            ->assertForbidden();
    }

    public function test_applicant_can_upload_application_document(): void
    {
        Storage::fake('local');

        $applicant = User::factory()->create([
            'role' => 'technician_applicant',
            'status' => 'active',
        ]);

        $this->actingAs($applicant, 'sanctum')
            ->postJson('/api/technician-applications', [
                'full_name' => 'Nuwan Perera',
                'date_of_birth' => '1993-02-10',
                'phone' => '0701234567',
                'address' => '22 Lake Road',
                'city' => 'Colombo',
                'years_experience' => 5,
                'highest_qualification' => 'NVQ Level 5',
                'skills' => ['ac'],
                'motivation' => 'I have practical field experience and want to serve customers professionally.',
            ])->assertCreated();

        $response = $this->actingAs($applicant, 'sanctum')
            ->postJson('/api/technician-applications/me/documents', [
                'document_type' => 'identity',
                'document' => UploadedFile::fake()->create('nic.pdf', 100, 'application/pdf'),
            ]);

        $response->assertCreated()
            ->assertJsonPath('document.document_type', 'identity')
            ->assertJsonMissingPath('document.stored_path');

        $document = TechnicianApplicationDocument::firstOrFail();

        Storage::disk('local')->assertExists($document->stored_path);
        $this->assertDatabaseHas('technician_application_documents', [
            'document_type' => 'identity',
            'status' => 'uploaded',
        ]);
    }

    public function test_invalid_application_document_type_is_rejected(): void
    {
        Storage::fake('local');

        $applicant = User::factory()->create([
            'role' => 'technician_applicant',
            'status' => 'active',
        ]);

        $this->actingAs($applicant, 'sanctum')
            ->postJson('/api/technician-applications', [
                'full_name' => 'Ravi Kumar',
                'date_of_birth' => '1991-07-18',
                'phone' => '0709876543',
                'address' => '44 Main Street',
                'city' => 'Kandy',
                'years_experience' => 3,
                'highest_qualification' => 'Diploma in Technical Services',
                'skills' => ['plumbing'],
                'motivation' => 'I want to join NimbusOps because I can deliver reliable technical services.',
            ])->assertCreated();

        $this->actingAs($applicant, 'sanctum')
            ->postJson('/api/technician-applications/me/documents', [
                'document_type' => 'bank_statement',
                'document' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
            ])
            ->assertUnprocessable();
    }

    public function test_customer_cannot_upload_technician_application_document(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/technician-applications/me/documents', [
                'document_type' => 'identity',
                'document' => UploadedFile::fake()->create('nic.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();
    }
}
