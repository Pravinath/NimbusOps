<?php

namespace App\Modules\Feedback\Services;

use App\Models\Complaint;
use App\Models\Feedback;
use App\Models\User;
use App\Models\WorkOrder;
use App\Modules\Audit\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeedbackService
{
    public function __construct(
        private AuditService $auditService
    ) {
    }

    public function submit(
        User $user,
        array $data
    ): Feedback {
        return DB::transaction(function () use ($user, $data) {
            $complaint = Complaint::query()
                ->with('customer')
                ->lockForUpdate()
                ->findOrFail($data['complaint_id']);

            $workOrder = WorkOrder::query()
                ->lockForUpdate()
                ->findOrFail($data['work_order_id']);

            if ($complaint->customer->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'complaint_id' => [
                        'This complaint does not belong to you.',
                    ],
                ]);
            }

            if ($workOrder->complaint_id !== $complaint->id) {
                throw ValidationException::withMessages([
                    'work_order_id' => [
                        'Work order does not belong to this complaint.',
                    ],
                ]);
            }

            if ($workOrder->status !== 'completed') {
                throw ValidationException::withMessages([
                    'work_order_id' => [
                        'Feedback is allowed only after job completion.',
                    ],
                ]);
            }

            $feedback = Feedback::create([
                'complaint_id' => $complaint->id,
                'work_order_id' => $workOrder->id,
                'customer_id' => $complaint->customer_id,
                'technician_id' => $workOrder->technician_id,
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
                'submitted_at' => now(),
            ]);

            $averageRating = Feedback::query()
                ->where('technician_id', $workOrder->technician_id)
                ->avg('rating');

            $workOrder->technician->update([
                'performance_score' => round(
                    (float) $averageRating,
                    2
                ),
            ]);

            $complaint->timelines()->create([
                'user_id' => $user->id,
                'event_type' => 'feedback_submitted',
                'from_status' => $complaint->status,
                'to_status' => $complaint->status,
                'notes' => $data['comment'] ?? 'Customer feedback submitted.',
                'metadata' => [
                    'rating' => $data['rating'],
                    'feedback_id' => $feedback->id,
                ],
            ]);

            $this->auditService->record(
                'feedback_submitted',
                $feedback,
                $user,
                request(),
                [
                    'complaint_id' => $complaint->id,
                    'work_order_id' => $workOrder->id,
                    'technician_id' => $workOrder->technician_id,
                    'rating' => $data['rating'],
                ]
            );

            return $feedback->load([
                'customer.user',
                'technician.user',
                'complaint',
                'workOrder',
            ]);
        });
    }
}
