<?php

namespace App\Modules\Reporting\Services;

use App\Models\Complaint;
use App\Models\ComplaintAiClassification;
use App\Models\Feedback;
use App\Models\ServiceArea;
use App\Models\SparePart;
use App\Models\Technician;
use App\Models\WorkOrderSparePart;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    public function dashboard(): array
    {
        $totalComplaints = Complaint::count();
        $resolvedComplaints = Complaint::whereIn('status', [
            'resolved',
            'closed',
        ])->count();

        return [
            'complaints' => [
                'total' => $totalComplaints,
                'pending' => Complaint::whereNotIn('status', [
                    'resolved',
                    'closed',
                    'cancelled',
                ])->count(),
                'resolved' => $resolvedComplaints,
                'cancelled' => Complaint::where('status', 'cancelled')->count(),
                'resolution_rate' => $this->percentage(
                    $resolvedComplaints,
                    $totalComplaints
                ),
            ],
            'sla' => $this->slaPerformance(),
            'customer_satisfaction' => $this->customerSatisfaction(),
            'technicians' => [
                'total' => Technician::count(),
                'available' => Technician::where(
                    'availability_status',
                    'available'
                )->count(),
                'busy' => Technician::where(
                    'availability_status',
                    'busy'
                )->count(),
            ],
            'inventory' => [
                'total_parts' => SparePart::count(),
                'low_stock_items' => SparePart::query()
                    ->where('status', 'active')
                    ->lowStock()
                    ->count(),
            ],
        ];
    }

    public function slaPerformance(): array
    {
        $tracked = Complaint::whereNotNull('sla_due_at')->count();
        $breached = Complaint::where('is_sla_breached', true)->count();

        return [
            'tracked' => $tracked,
            'breached' => $breached,
            'within_sla' => max($tracked - $breached, 0),
            'compliance_rate' => $this->percentage(
                max($tracked - $breached, 0),
                $tracked
            ),
        ];
    }

    public function technicianPerformance(): Collection
    {
        return Technician::with('user:id,name,email')
            ->orderByDesc('performance_score')
            ->get()
            ->map(fn (Technician $technician) => [
                'technician_id' => $technician->id,
                'name' => $technician->user?->name,
                'skill_category' => $technician->skill_category,
                'availability_status' => $technician->availability_status,
                'current_workload' => $technician->current_workload,
                'performance_score' => (float) $technician->performance_score,
                'feedback_count' => $technician->feedback()->count(),
                'completed_work_orders' => $technician->workOrders()
                    ->where('status', 'completed')
                    ->count(),
            ]);
    }

    public function areaWiseComplaints(): Collection
    {
        return ServiceArea::withCount([
            'complaints',
            'complaints as pending_complaints_count' => fn ($query) => $query->whereNotIn('status', [
                'resolved',
                'closed',
                'cancelled',
            ]),
            'complaints as resolved_complaints_count' => fn ($query) => $query->whereIn('status', ['resolved', 'closed']),
        ])->orderByDesc('complaints_count')->get();
    }

    public function commonIssueCategories(): Collection
    {
        return ComplaintAiClassification::query()
            ->select('issue_category', DB::raw('COUNT(*) as total'))
            ->groupBy('issue_category')
            ->orderByDesc('total')
            ->get();
    }

    public function sparePartsUsage(): Collection
    {
        return WorkOrderSparePart::query()
            ->join('spare_parts', 'spare_parts.id', '=', 'work_order_spare_parts.spare_part_id')
            ->select([
                'spare_parts.id as spare_part_id',
                'spare_parts.sku',
                'spare_parts.name',
                DB::raw('SUM(work_order_spare_parts.quantity) as total_quantity'),
                DB::raw('SUM(work_order_spare_parts.total_cost) as total_cost'),
            ])
            ->groupBy('spare_parts.id', 'spare_parts.sku', 'spare_parts.name')
            ->orderByDesc('total_quantity')
            ->get();
    }

    public function customerSatisfaction(): array
    {
        $count = Feedback::count();

        return [
            'feedback_count' => $count,
            'average_rating' => round((float) (Feedback::avg('rating') ?? 0), 2),
            'five_star_count' => Feedback::where('rating', 5)->count(),
        ];
    }

    public function monthlyComplaintTrends(): Collection
    {
        return Complaint::query()
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->get(['id', 'status', 'created_at'])
            ->groupBy(fn (Complaint $complaint) => $complaint->created_at->format('Y-m'))
            ->map(fn (Collection $complaints, string $month) => [
                'month' => $month,
                'total' => $complaints->count(),
                'resolved' => $complaints->whereIn('status', [
                    'resolved',
                    'closed',
                ])->count(),
            ])
            ->sortBy('month')
            ->values();
    }

    private function percentage(int $value, int $total): float
    {
        if ($total === 0) {
            return 0;
        }

        return round(($value / $total) * 100, 2);
    }
}
