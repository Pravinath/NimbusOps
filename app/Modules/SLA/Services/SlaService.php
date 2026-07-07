<?php

namespace App\Modules\SLA\Services;

use App\Models\Complaint;
use App\Models\SlaPolicy;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class SlaService
{
    public function __construct(
        private AuditService $auditService,
        private NotificationService $notificationService
    ) {}

    public function assignDeadline(Complaint $complaint): Complaint
    {
        $policy = SlaPolicy::query()
            ->where('priority', $complaint->priority)
            ->where('is_active', true)
            ->first();

        $resolutionMinutes = $policy?->resolution_minutes
            ?? match ($complaint->priority) {
                'critical' => 120,
                'high' => 240,
                'medium' => 720,
                default => 1440,
            };

        $startTime = $complaint->created_at ?? now();

        $complaint->update([
            'sla_due_at' => $startTime
                ->copy()
                ->addMinutes($resolutionMinutes),
            'is_sla_breached' => false,
            'sla_breached_at' => null,
            'sla_escalated_at' => null,
        ]);

        return $complaint->fresh();
    }

    public function detectBreaches(): int
    {
        $breachCount = 0;

        Complaint::query()
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', now())
            ->where('is_sla_breached', false)
            ->whereNotIn('status', [
                'resolved',
                'closed',
                'cancelled',
            ])
            ->chunkById(100, function ($complaints) use (&$breachCount) {
                foreach ($complaints as $complaint) {
                    $this->markBreached($complaint);
                    $breachCount++;
                }
            });

        return $breachCount;
    }

    private function markBreached(Complaint $complaint): void
    {
        DB::transaction(function () use ($complaint) {
            $complaint = Complaint::query()
                ->lockForUpdate()
                ->findOrFail($complaint->id);

            if (
                $complaint->is_sla_breached
                || $complaint->sla_due_at === null
                || $complaint->sla_due_at->isFuture()
                || in_array($complaint->status, [
                    'resolved',
                    'closed',
                    'cancelled',
                ], true)
            ) {
                return;
            }

            $complaint->update([
                'is_sla_breached' => true,
                'sla_breached_at' => now(),
                'sla_escalated_at' => now(),
            ]);

            $complaint->timelines()->create([
                'event_type' => 'sla_breached',
                'from_status' => $complaint->status,
                'to_status' => $complaint->status,
                'notes' => 'SLA deadline breached and escalated to supervisors.',
                'metadata' => [
                    'sla_due_at' => $complaint->sla_due_at->toISOString(),
                    'priority' => $complaint->priority,
                ],
            ]);

            $this->auditService->record(
                'sla_breached',
                $complaint,
                null,
                null,
                [
                    'priority' => $complaint->priority,
                    'sla_due_at' => $complaint->sla_due_at->toISOString(),
                ]
            );

            $this->notificationService->slaBreached($complaint);
        });
    }
}
