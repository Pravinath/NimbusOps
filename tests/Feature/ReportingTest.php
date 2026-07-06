<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Customer;
use App\Models\Feedback;
use App\Models\ServiceArea;
use App\Models\SparePart;
use App\Models\Technician;
use App\Models\TechnicianAssignment;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderSparePart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_returns_correct_summary(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->seedReportingData();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.complaints.total', 2)
            ->assertJsonPath('data.complaints.pending', 1)
            ->assertJsonPath('data.complaints.resolved', 1)
            ->assertJsonPath('data.sla.breached', 1)
            ->assertJsonPath('data.customer_satisfaction.average_rating', 5)
            ->assertJsonPath('data.inventory.low_stock_items', 1);
    }

    public function test_operational_reports_return_aggregated_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->seedReportingData();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/reports/technician-performance')
            ->assertOk()
            ->assertJsonPath('data.0.feedback_count', 1)
            ->assertJsonPath('data.0.completed_work_orders', 1);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/reports/area-wise-complaints')
            ->assertOk()
            ->assertJsonPath('data.0.complaints_count', 2)
            ->assertJsonPath('data.0.pending_complaints_count', 1);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/reports/common-issue-categories')
            ->assertOk()
            ->assertJsonPath('data.0.issue_category', 'internet_connectivity');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/reports/spare-parts-usage')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Router Adapter');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/reports/monthly-complaint-trends')
            ->assertOk()
            ->assertJsonPath('data.0.total', 2);
    }

    public function test_reporting_routes_enforce_roles(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $dispatcher = User::factory()->create(['role' => 'dispatcher']);
        $inventory = User::factory()->create(['role' => 'inventory']);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/admin/dashboard')
            ->assertForbidden();

        $this->actingAs($dispatcher, 'sanctum')
            ->getJson('/api/reports/technician-performance')
            ->assertOk();

        $this->actingAs($dispatcher, 'sanctum')
            ->getJson('/api/reports/sla-performance')
            ->assertForbidden();

        $this->actingAs($inventory, 'sanctum')
            ->getJson('/api/reports/spare-parts-usage')
            ->assertOk();
    }

    private function seedReportingData(): void
    {
        $customerUser = User::factory()->create(['role' => 'customer']);
        $technicianUser = User::factory()->create(['role' => 'technician']);
        $dispatcher = User::factory()->create(['role' => 'dispatcher']);

        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'address' => '10 Main Street',
            'city' => 'Colombo',
        ]);

        $area = ServiceArea::create([
            'name' => 'Colombo Central',
            'city' => 'Colombo',
            'zone' => 'Zone 1',
        ]);

        $technician = Technician::create([
            'user_id' => $technicianUser->id,
            'service_area_id' => $area->id,
            'skill_category' => 'network',
            'availability_status' => 'available',
            'performance_score' => 5,
        ]);

        $resolved = Complaint::create([
            'customer_id' => $customer->id,
            'service_area_id' => $area->id,
            'created_by_user_id' => $customerUser->id,
            'title' => 'Internet unavailable',
            'description' => 'The connection was down.',
            'status' => 'resolved',
            'priority' => 'high',
            'sla_due_at' => now()->addHour(),
            'resolved_at' => now(),
        ]);

        Complaint::create([
            'customer_id' => $customer->id,
            'service_area_id' => $area->id,
            'created_by_user_id' => $customerUser->id,
            'title' => 'Router issue',
            'description' => 'The router is offline.',
            'status' => 'new',
            'priority' => 'critical',
            'sla_due_at' => now()->subHour(),
            'is_sla_breached' => true,
            'sla_breached_at' => now(),
        ]);

        $resolved->aiClassification()->create([
            'provider' => 'mock',
            'issue_category' => 'internet_connectivity',
            'predicted_priority' => 'high',
            'suggested_skill' => 'network',
            'suggested_spare_parts' => ['router adapter'],
            'suggested_sla_minutes' => 240,
            'repeated_complaint_risk' => false,
            'summary' => 'Internet connection was unavailable.',
            'confidence_score' => 88,
            'raw_response' => [],
            'classified_at' => now(),
        ]);

        $assignment = TechnicianAssignment::create([
            'complaint_id' => $resolved->id,
            'technician_id' => $technician->id,
            'assigned_by_user_id' => $dispatcher->id,
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        $workOrder = WorkOrder::create([
            'complaint_id' => $resolved->id,
            'technician_assignment_id' => $assignment->id,
            'technician_id' => $technician->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        Feedback::create([
            'complaint_id' => $resolved->id,
            'work_order_id' => $workOrder->id,
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'rating' => 5,
            'comment' => 'Excellent service.',
            'submitted_at' => now(),
        ]);

        $part = SparePart::create([
            'sku' => 'LOW-REPORT-1',
            'name' => 'Router Adapter',
            'stock_quantity' => 1,
            'reorder_level' => 2,
            'unit_cost' => 1500,
        ]);

        WorkOrderSparePart::create([
            'work_order_id' => $workOrder->id,
            'spare_part_id' => $part->id,
            'technician_id' => $technician->id,
            'quantity' => 2,
            'unit_cost' => 1500,
            'total_cost' => 3000,
            'used_at' => now(),
        ]);
    }
}
