<?php

namespace App\Modules\WorkOrder\Policies;

use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            'technician',
            'dispatcher',
            'supervisor',
            'admin',
        ], true);
    }

    public function view(User $user, WorkOrder $workOrder): bool
    {
        if (in_array($user->role, [
            'dispatcher',
            'supervisor',
            'admin',
        ], true)) {
            return true;
        }

        return $this->isAssignedTechnician($user, $workOrder);
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $this->isAssignedTechnician($user, $workOrder);
    }

    private function isAssignedTechnician(
        User $user,
        WorkOrder $workOrder
    ): bool {
        return $user->role === 'technician'
            && $workOrder->technician?->user_id === $user->id;
    }
}
