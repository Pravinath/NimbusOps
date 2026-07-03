<?php

namespace App\Modules\Complaint\Services;

use App\Models\Complaint;
use Illuminate\Validation\ValidationException;

class ComplaintStatusService
{
    private const TRANSITIONS = [
        'new' => ['classified', 'cancelled'],
        'classified' => ['assigned', 'escalated', 'cancelled'],
        'assigned' => ['technician_on_the_way', 'escalated', 'cancelled'],
        'technician_on_the_way' => ['in_progress', 'escalated'],
        'in_progress' => ['resolved', 'escalated'],
        'resolved' => ['closed', 'in_progress'],
        'escalated' => ['assigned', 'in_progress', 'resolved', 'cancelled'],
        'closed' => [],
        'cancelled' => [],
    ];

    public function ensureCanTransition(
        Complaint $complaint,
        string $newStatus
    ): void {
        $allowedStatuses = self::TRANSITIONS[$complaint->status] ?? [];

        if (! in_array($newStatus, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => [
                    "Cannot change complaint from {$complaint->status} to {$newStatus}.",
                ],
            ]);
        }
    }
}