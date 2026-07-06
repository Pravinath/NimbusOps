<?php

namespace App\Modules\Complaint\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Modules\Complaint\Requests\StoreComplaintRequest;
use App\Modules\Complaint\Requests\UpdateComplaintStatusRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Modules\Complaint\Services\ComplaintStatusService;
use App\Modules\SLA\Services\SlaService;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Notification\Services\NotificationService;

class ComplaintController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Complaint::class);

        $query = Complaint::with([
            'customer.user',
            'serviceArea',
            'createdBy',
        ])->latest();

        if ($request->user()->role === 'customer') {
            $query->whereHas('customer', function ($customerQuery) use ($request) {
                $customerQuery->where('user_id', $request->user()->id);
            });
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    public function store(StoreComplaintRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $complaint = DB::transaction(function () use ($request, $validated) {
            $complaint = Complaint::create([
                'customer_id' => $validated['customer_id'],
                'service_area_id' => $validated['service_area_id'] ?? null,
                'created_by_user_id' => $request->user()->id,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'preferred_visit_time' => $validated['preferred_visit_time'] ?? null,
                'status' => 'new',
                'priority' => $validated['priority'] ?? 'medium',
            ]);

            $complaint->timelines()->create([
                'user_id' => $request->user()->id,
                'event_type' => 'complaint_created',
                'to_status' => 'new',
                'notes' => 'Complaint created.',
            ]);

            return $complaint;
        });

        $complaint = $this->slaService->assignDeadline($complaint);

        $complaint->load([
            'customer.user',
            'serviceArea',
            'createdBy',
            'timelines',
        ]);

        $this->auditService->record(
            'complaint_created',
            $complaint,
            $request->user(),
            $request,
            [
                'customer_id' => $complaint->customer_id,
                'priority' => $complaint->priority,
                'status' => $complaint->status,
            ]
        );

        $this->notificationService->complaintCreated($complaint);

        return response()->json([
            'message' => 'Complaint created successfully.',
            'data' => $complaint,
        ], 201);
    }

    public function show(Complaint $complaint): JsonResponse
    {
        Gate::authorize('view', $complaint);

        $complaint->load([
            'customer.user',
            'serviceArea',
            'createdBy',
            'timelines.user',
        ]);

        return response()->json([
            'data' => $complaint,
        ]);
    }

    public function updateStatus(
        UpdateComplaintStatusRequest $request,
        Complaint $complaint
    ): JsonResponse {
        Gate::authorize('updateStatus', $complaint);

        $oldStatus = $complaint->status;
        $newStatus = $request->validated('status');
        $this->statusService->ensureCanTransition($complaint, $newStatus);

        DB::transaction(function () use (
            $request,
            $complaint,
            $oldStatus,
            $newStatus
        ) {
            $complaint->status = $newStatus;

            if ($newStatus === 'resolved') {
                $complaint->resolved_at = now();
            }

            $complaint->save();

            $complaint->timelines()->create([
                'user_id' => $request->user()->id,
                'event_type' => 'status_changed',
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'notes' => $request->validated('notes'),
            ]);
        });

        return response()->json([
            'message' => 'Complaint status updated successfully.',
            'data' => $complaint->fresh(),
        ]);
    }

    public function timeline(Complaint $complaint): JsonResponse
    {
        Gate::authorize('view', $complaint);

        return response()->json([
            'data' => $complaint->timelines()
                ->with('user:id,name,email')
                ->get(),
        ]);
    }

    public function __construct(
        private ComplaintStatusService $statusService,
        private SlaService $slaService,
        private AuditService $auditService,
        private NotificationService $notificationService

    ) {
    }
}
