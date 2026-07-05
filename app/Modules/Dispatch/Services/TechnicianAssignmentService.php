<?php

namespace App\Modules\Dispatch\Services;

use App\Models\Complaint;
use App\Models\Technician;
use App\Models\TechnicianAssignment;
use App\Models\User;
use App\Models\WorkOrder;
use App\Modules\Complaint\Services\ComplaintStatusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TechnicianAssignmentService
{
    public function __construct(
        private ComplaintStatusService $statusService
    ) {
    }

    public function assign(
        Complaint $complaint,
        Technician $technician,
        User $assignedBy,
        array $data
    ): TechnicianAssignment {
        return DB::transaction(function () use (
            $complaint,
            $technician,
            $assignedBy,
            $data
        ) {
            $complaint = Complaint::query()
                ->lockForUpdate()
                ->findOrFail($complaint->id);

            $technician = Technician::query()
                ->lockForUpdate()
                ->findOrFail($technician->id);

            if ($complaint->workOrder()->exists()) {
                throw ValidationException::withMessages([
                    'complaint' => [
                        'This complaint already has a work order.',
                    ],
                ]);
            }

            $overrideRequested = (bool) ($data['override'] ?? false);
            $overrideAllowed = $assignedBy->role === 'admin'
                && $overrideRequested;

            if (
                $technician->availability_status !== 'available'
                && ! $overrideAllowed
            ) {
                throw ValidationException::withMessages([
                    'technician_id' => [
                        'The selected technician is not available.',
                    ],
                ]);
            }

            $this->statusService->ensureCanTransition(
                $complaint,
                'assigned'
            );

            $classification = $complaint->aiClassification;

            $assignment = TechnicianAssignment::create([
                'complaint_id' => $complaint->id,
                'technician_id' => $technician->id,
                'assigned_by_user_id' => $assignedBy->id,
                'status' => 'active',
                'is_override' => $overrideAllowed,
                'notes' => $data['notes'] ?? null,
                'assigned_at' => now(),
            ]);

            WorkOrder::create([
                'complaint_id' => $complaint->id,
                'technician_assignment_id' => $assignment->id,
                'technician_id' => $technician->id,
                'scheduled_visit_time' => $data['scheduled_visit_time']
                    ?? $complaint->preferred_visit_time,
                'required_skill' => $classification?->suggested_skill
                    ?? $technician->skill_category,
                'suggested_spare_parts' => $classification
                    ?->suggested_spare_parts ?? [],
                'status' => 'created',
            ]);

            $oldStatus = $complaint->status;

            $complaint->update([
                'status' => 'assigned',
            ]);

            $technician->increment('current_workload');

            $technician->update([
                'availability_status' => 'busy',
            ]);

            $complaint->timelines()->create([
                'user_id' => $assignedBy->id,
                'event_type' => 'technician_assigned',
                'from_status' => $oldStatus,
                'to_status' => 'assigned',
                'notes' => $data['notes']
                    ?? 'Technician assigned.',
                'metadata' => [
                    'technician_id' => $technician->id,
                    'assignment_id' => $assignment->id,
                    'override' => $overrideAllowed,
                ],
            ]);

            return $assignment->load([
                'technician.user',
                'assignedBy',
                'workOrder',
            ]);
        });
    }
}