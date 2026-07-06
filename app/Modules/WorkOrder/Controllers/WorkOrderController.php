<?php

namespace App\Modules\WorkOrder\Controllers;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Modules\WorkOrder\Requests\AddWorkOrderUpdateRequest;
use App\Modules\WorkOrder\Requests\CompleteWorkOrderRequest;
use App\Modules\WorkOrder\Requests\WorkOrderActionRequest;
use App\Modules\WorkOrder\Services\WorkOrderStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WorkOrderController extends Controller
{
    public function __construct(
        private WorkOrderStatusService $statusService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', WorkOrder::class);

        $query = WorkOrder::with([
            'complaint.customer.user',
            'technician.user',
            'assignment',
        ])->latest();

        if ($request->user()->role === 'technician') {
            $query->whereHas('technician', function ($technicianQuery) use ($request) {
                $technicianQuery->where(
                    'user_id',
                    $request->user()->id
                );
            });
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    public function show(WorkOrder $workOrder): JsonResponse
    {
        Gate::authorize('view', $workOrder);

        return response()->json([
            'data' => $workOrder->load([
                'complaint.customer.user',
                'technician.user',
                'assignment.assignedBy',
                'updates.user',
            ]),
        ]);
    }

    public function accept(
        WorkOrderActionRequest $request,
        WorkOrder $workOrder
    ): JsonResponse {
        return $this->changeStatus(
            $request,
            $workOrder,
            'accepted'
        );
    }

    public function onTheWay(
        WorkOrderActionRequest $request,
        WorkOrder $workOrder
    ): JsonResponse {
        return $this->changeStatus(
            $request,
            $workOrder,
            'on_the_way'
        );
    }

    public function start(
        WorkOrderActionRequest $request,
        WorkOrder $workOrder
    ): JsonResponse {
        return $this->changeStatus(
            $request,
            $workOrder,
            'started'
        );
    }

    public function pause(
        WorkOrderActionRequest $request,
        WorkOrder $workOrder
    ): JsonResponse {
        return $this->changeStatus(
            $request,
            $workOrder,
            'paused'
        );
    }

    public function complete(
        CompleteWorkOrderRequest $request,
        WorkOrder $workOrder
    ): JsonResponse {
        Gate::authorize('update', $workOrder);

        $workOrder = $this->statusService->transition(
            $workOrder,
            $request->user(),
            'completed',
            $request->validated('notes')
        );

        $workOrder->update([
            'resolution_summary' => $request->validated(
                'resolution_summary'
            ),
            'after_photo_metadata' => $request->validated(
                'after_photo_metadata'
            ),
        ]);

        return response()->json([
            'message' => 'Work order completed successfully.',
            'data' => $workOrder->fresh(),
        ]);
    }

    public function addUpdate(
        AddWorkOrderUpdateRequest $request,
        WorkOrder $workOrder
    ): JsonResponse {
        Gate::authorize('update', $workOrder);

        $update = $workOrder->updates()->create([
            'user_id' => $request->user()->id,
            'update_type' => 'note_added',
            'notes' => $request->validated('notes'),
            'metadata' => $request->validated('metadata'),
        ]);

        return response()->json([
            'message' => 'Work order update added successfully.',
            'data' => $update,
        ], 201);
    }

    private function changeStatus(
        WorkOrderActionRequest $request,
        WorkOrder $workOrder,
        string $status
    ): JsonResponse {
        Gate::authorize('update', $workOrder);

        $workOrder = $this->statusService->transition(
            $workOrder,
            $request->user(),
            $status,
            $request->validated('notes')
        );

        return response()->json([
            'message' => 'Work order status updated successfully.',
            'data' => $workOrder,
        ]);
    }
}