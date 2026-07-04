<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\ComplaintAiClassification;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIClassificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatcher_can_classify_internet_complaint(): void
    {
        $customerUser = User::factory()->create([
            'role' => 'customer',
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
            'status' => 'active',
        ]);

        $complaint = Complaint::create([
            'customer_id' => $customer->id,
            'created_by_user_id' => $customerUser->id,
            'title' => 'Internet not working',
            'description' => 'Router red light is blinking and internet is completely down.',
            'status' => 'new',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($dispatcher, 'sanctum')
            ->postJson(
                "/api/complaints/{$complaint->id}/ai-classify"
            );

        $response->assertOk()
            ->assertJsonPath(
                'data.issue_category',
                'internet_connectivity'
            )
            ->assertJsonPath('data.predicted_priority', 'high')
            ->assertJsonPath('data.suggested_skill', 'network')
            ->assertJsonPath('data.suggested_sla_minutes', 240);

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'classified',
            'priority' => 'high',
        ]);

        $this->assertDatabaseHas('complaint_ai_classifications', [
            'complaint_id' => $complaint->id,
            'provider' => 'mock',
            'issue_category' => 'internet_connectivity',
        ]);

        $this->assertDatabaseHas('complaint_timelines', [
            'complaint_id' => $complaint->id,
            'event_type' => 'ai_classification_generated',
            'to_status' => 'classified',
        ]);
    }

    public function test_customer_can_view_own_classification(): void
    {
        $customerUser = User::factory()->create([
            'role' => 'customer',
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
            'title' => 'Water leak',
            'description' => 'Water is leaking from a pipe.',
        ]);

        ComplaintAiClassification::create([
            'complaint_id' => $complaint->id,
            'provider' => 'mock',
            'issue_category' => 'water_leak',
            'predicted_priority' => 'high',
            'suggested_skill' => 'plumbing',
            'suggested_spare_parts' => ['pipe fitting'],
            'suggested_sla_minutes' => 240,
            'repeated_complaint_risk' => false,
            'summary' => 'Water is leaking from a pipe.',
            'confidence_score' => 88,
            'raw_response' => [],
            'classified_at' => now(),
        ]);

        $this->actingAs($customerUser, 'sanctum')
            ->getJson(
                "/api/complaints/{$complaint->id}/ai-classification"
            )
            ->assertOk()
            ->assertJsonPath('data.issue_category', 'water_leak');
    }

    public function test_customer_cannot_trigger_ai_classification(): void
    {
        $customerUser = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
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
            'description' => 'Internet is not working.',
        ]);

        $this->actingAs($customerUser, 'sanctum')
            ->postJson(
                "/api/complaints/{$complaint->id}/ai-classify"
            )
            ->assertForbidden();
    }
}