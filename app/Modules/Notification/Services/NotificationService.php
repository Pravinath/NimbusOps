<?php

namespace App\Modules\Notification\Services;

use App\Models\Complaint;
use App\Models\SparePart;
use App\Models\TechnicianAssignment;
use App\Models\User;
use App\Models\WorkOrder;
use App\Modules\Notification\Notifications\DatabaseEventNotification;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function complaintCreated(Complaint $complaint): void
    {
        $customerUser = $complaint->customer?->user;

        $customerUser?->notify(new DatabaseEventNotification(
            'complaint_created',
            'Complaint received',
            "Your complaint #{$complaint->id} has been created.",
            ['complaint_id' => $complaint->id]
        ));
    }

    public function technicianAssigned(TechnicianAssignment $assignment): void
    {
        $technicianUser = $assignment->technician?->user;

        $technicianUser?->notify(new DatabaseEventNotification(
            'technician_assigned',
            'New work assignment',
            "You have been assigned complaint #{$assignment->complaint_id}.",
            [
                'complaint_id' => $assignment->complaint_id,
                'assignment_id' => $assignment->id,
                'work_order_id' => $assignment->workOrder?->id,
            ]
        ));
    }

    public function complaintResolved(WorkOrder $workOrder): void
    {
        $customerUser = $workOrder->complaint?->customer?->user;

        $customerUser?->notify(new DatabaseEventNotification(
            'complaint_resolved',
            'Complaint resolved',
            "Your complaint #{$workOrder->complaint_id} has been resolved.",
            [
                'complaint_id' => $workOrder->complaint_id,
                'work_order_id' => $workOrder->id,
            ]
        ));
    }

    public function slaBreached(Complaint $complaint): void
    {
        $recipients = User::query()
            ->whereIn('role', ['supervisor', 'admin'])
            ->where('status', 'active')
            ->get();

        Notification::send($recipients, new DatabaseEventNotification(
            'sla_breached',
            'SLA breach detected',
            "Complaint #{$complaint->id} has breached its SLA.",
            [
                'complaint_id' => $complaint->id,
                'priority' => $complaint->priority,
                'sla_due_at' => $complaint->sla_due_at?->toISOString(),
            ]
        ));
    }

    public function lowStock(SparePart $sparePart): void
    {
        $recipients = User::query()
            ->whereIn('role', ['inventory', 'admin'])
            ->where('status', 'active')
            ->get();

        Notification::send($recipients, new DatabaseEventNotification(
            'low_stock',
            'Low stock alert',
            "{$sparePart->name} is at or below its reorder level.",
            [
                'spare_part_id' => $sparePart->id,
                'stock_quantity' => $sparePart->stock_quantity,
                'reorder_level' => $sparePart->reorder_level,
            ]
        ));
    }
}
