<?php

namespace App\Modules\Complaint\Policies;

use App\Models\Complaint;
use App\Models\User;

class ComplaintPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            'customer',
            'agent',
            'dispatcher',
            'supervisor',
            'admin',
        ], true);
    }

    public function view(User $user, Complaint $complaint): bool
    {
        if ($user->role === 'customer') {
            return $complaint->customer?->user_id === $user->id;
        }

        return in_array($user->role, [
            'agent',
            'dispatcher',
            'supervisor',
            'admin',
        ], true);
    }

    public function updateStatus(User $user, Complaint $complaint): bool
    {
        return in_array($user->role, [
            'agent',
            'dispatcher',
            'supervisor',
            'admin',
        ], true);
    }
}
