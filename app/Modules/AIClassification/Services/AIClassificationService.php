<?php

namespace App\Modules\AIClassification\Services;

use App\Models\Complaint;
use App\Models\ComplaintAiClassification;
use App\Modules\AIClassification\Contracts\AIClassificationProvider;
use Illuminate\Support\Facades\DB;
use App\Modules\SLA\Services\SlaService;
use App\Models\User;
use App\Modules\Audit\Services\AuditService;


class AIClassificationService
{
    public function __construct(
        private AIClassificationProvider $provider,
        private SlaService $slaService,
        private AuditService $auditService
    ) {
    }

    public function classify(
        Complaint $complaint,
        int $userId
    ): ComplaintAiClassification {
        $result = $this->provider->classify($complaint);

        return DB::transaction(function () use (
            $complaint,
            $result,
            $userId
        ) {
            $oldStatus = $complaint->status;

            $classification = ComplaintAiClassification::updateOrCreate(
                ['complaint_id' => $complaint->id],
                [
                    'provider' => $this->provider->name(),
                    'issue_category' => $result['issue_category'],
                    'predicted_priority' => $result['predicted_priority'],
                    'suggested_skill' => $result['suggested_skill'],
                    'suggested_spare_parts' => $result['suggested_spare_parts'],
                    'suggested_sla_minutes' => $result['suggested_sla_minutes'],
                    'repeated_complaint_risk' => $result['repeated_complaint_risk'],
                    'summary' => $result['summary'],
                    'confidence_score' => $result['confidence_score'],
                    'raw_response' => $result,
                    'classified_at' => now(),
                ]
            );

            $complaint->priority = $result['predicted_priority'];

            if ($complaint->status === 'new') {
                $complaint->status = 'classified';
            }

            $complaint->save();
            $complaint = $this->slaService->assignDeadline($complaint);

            $complaint->timelines()->create([
                'user_id' => $userId,
                'event_type' => 'ai_classification_generated',
                'from_status' => $oldStatus,
                'to_status' => $complaint->status,
                'notes' => sprintf(
                    'Classified as %s with %s priority.',
                    $result['issue_category'],
                    $result['predicted_priority']
                ),
                'metadata' => [
                    'provider' => $this->provider->name(),
                    'suggested_skill' => $result['suggested_skill'],
                    'confidence_score' => $result['confidence_score'],
                ],
            ]);

            $this->auditService->record(
                'ai_classification_generated',
                $classification,
                User::find($userId),
                request(),
                [
                    'complaint_id' => $complaint->id,
                    'issue_category' => $result['issue_category'],
                    'predicted_priority' => $result['predicted_priority'],
                    'suggested_skill' => $result['suggested_skill'],
                    'provider' => $this->provider->name(),
                ]
            );

            return $classification->fresh();
        });
    }
}
