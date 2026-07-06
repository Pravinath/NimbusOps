<?php

namespace App\Modules\WorkOrder\Services;

use App\Models\User;
use App\Models\WorkOrder;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Complaint\Services\ComplaintStatusService;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkOrderStatusService
{
    private const TRANSITIONS = [
        'created' => ['accepted'],
        'accepted' => ['on_the_way'],
        'on_the_way' => ['started'],
        'started' => ['paused', 'completed'],
        'paused' => ['started', 'completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function __construct(
        private ComplaintStatusService $complaintStatusService,
        private AuditService $auditService,
        private NotificationService $notificationService
    ) {
    }

    public function transition(
        WorkOrder $workOrder,
        User $user,
        string $newStatus,
        ?string $notes = null
    ): WorkOrder {
        return DB::transaction(function () use (
            $workOrder,
            $user,
            $newStatus,
            $notes
        ) {
            $workOrder = WorkOrder::query()
                ->lockForUpdate()
                ->findOrFail($workOrder->id);

            $allowed = self::TRANSITIONS[$workOrder->status] ?? [];

            if (! in_array($newStatus, $allowed, true)) {
                throw ValidationException::withMessages([
                    'status' => [
                        "Cannot change work order from {$workOrder->status} to {$newStatus}.",
                    ],
                ]);
            }

            $oldStatus = $workOrder->status;
            $complaintStatus = $this->complaintStatusFor($newStatus);

            if ($complaintStatus !== null) {
                $this->complaintStatusService->ensureCanTransition(
                    $workOrder->complaint,
                    $complaintStatus
                );
            }

            $workOrder->status = $newStatus;

            match ($newStatus) {
                'accepted' => $workOrder->accepted_at = now(),
                'on_the_way' => $workOrder->on_the_way_at = now(),
                'started' => $workOrder->started_at = now(),
                'completed' => $workOrder->completed_at = now(),
                default => null,
            };

            $workOrder->save();

            $workOrder->updates()->create([
                'user_id' => $user->id,
                'update_type' => 'status_changed',
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'notes' => $notes,
            ]);

            if ($complaintStatus !== null) {
                $complaint = $workOrder->complaint;
                $oldComplaintStatus = $complaint->status;

                $complaint->status = $complaintStatus;

                if ($complaintStatus === 'resolved') {
                    $complaint->resolved_at = now();
                }

                $complaint->save();

                $complaint->timelines()->create([
                    'user_id' => $user->id,
                    'event_type' => 'work_order_status_changed',
                    'from_status' => $oldComplaintStatus,
                    'to_status' => $complaintStatus,
                    'notes' => $notes,
                    'metadata' => [
                        'work_order_id' => $workOrder->id,
                        'work_order_status' => $newStatus,
                    ],
                ]);
            }

            if ($newStatus === 'completed') {
                $technician = $workOrder->technician;

                if ($technician->current_workload > 0) {
                    $technician->decrement('current_workload');
                }

                $technician->refresh();

                if ($technician->current_workload === 0) {
                    $technician->update([
                        'availability_status' => 'available',
                    ]);
                }

                $this->auditService->record(
                    'work_order_completed',
                    $workOrder,
                    $user,
                    request(),
                    [
                        'complaint_id' => $workOrder->complaint_id,
                        'technician_id' => $workOrder->technician_id,
                        'from_status' => $oldStatus,
                        'to_status' => $newStatus,
                    ]
                );

                $workOrder->loadMissing('complaint.customer.user');
                $this->notificationService->complaintResolved($workOrder);
            }

            return $workOrder->fresh([
                'complaint',
                'technician.user',
                'updates.user',
            ]);
        });
    }

    private function complaintStatusFor(string $workOrderStatus): ?string
    {
        return match ($workOrderStatus) {
            'on_the_way' => 'technician_on_the_way',
            'started' => 'in_progress',
            'completed' => 'resolved',
            default => null,
        };
    }
}
