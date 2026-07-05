<?php

namespace App\Modules\Dispatch\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Technician;
use App\Modules\Dispatch\Requests\AssignTechnicianRequest;
use App\Modules\Dispatch\Services\TechnicianAssignmentService;
use App\Modules\Dispatch\Services\TechnicianSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class DispatchController extends Controller
{
    public function __construct(
        private TechnicianSuggestionService $suggestionService,
        private TechnicianAssignmentService $assignmentService
    ) {
    }

    public function suggestions(Complaint $complaint): JsonResponse
    {
        Gate::authorize('updateStatus', $complaint);

        return response()->json([
            'data' => $this->suggestionService->suggest($complaint),
        ]);
    }

    public function assign(
        AssignTechnicianRequest $request,
        Complaint $complaint
    ): JsonResponse {
        Gate::authorize('updateStatus', $complaint);

        $technician = Technician::findOrFail(
            $request->validated('technician_id')
        );

        $assignment = $this->assignmentService->assign(
            $complaint,
            $technician,
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Technician assigned successfully.',
            'data' => $assignment,
        ], 201);
    }
}