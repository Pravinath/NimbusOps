<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_audit_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'stock_updated',
            'entity_type' => User::class,
            'entity_id' => $admin->id,
            'metadata' => ['quantity_after' => 10],
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/audit-logs?action=stock_updated')
            ->assertOk()
            ->assertJsonPath('data.data.0.action', 'stock_updated');
    }

    public function test_non_admin_cannot_view_audit_logs(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($supervisor, 'sanctum')
            ->getJson('/api/audit-logs')
            ->assertForbidden();
    }

    public function test_complaint_creation_is_audited(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $customer = Customer::create([
            'user_id' => $user->id,
            'address' => '10 Main Street',
            'city' => 'Colombo',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/complaints', [
                'customer_id' => $customer->id,
                'title' => 'Internet unavailable',
                'description' => 'The connection is not working.',
                'priority' => 'high',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'complaint_created',
            'entity_id' => $response->json('data.id'),
        ]);
    }
}
