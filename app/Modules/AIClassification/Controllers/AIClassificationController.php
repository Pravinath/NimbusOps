<?php

namespace App\Modules\AIClassification\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Modules\AIClassification\Services\AIClassificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AIClassificationController extends Controller
{
    public function __construct(
        private AIClassificationService $classificationService
    ) {}

    public function classify(
        Request $request,
        Complaint $complaint
    ): JsonResponse {
        Gate::authorize('updateStatus', $complaint);

        $classification = $this->classificationService->classify(
            $complaint,
            $request->user()->id
        );

        return response()->json([
            'message' => 'Complaint classified successfully.',
            'data' => $classification,
        ]);
    }

    public function show(Complaint $complaint): JsonResponse
    {
        Gate::authorize('view', $complaint);

        $classification = $complaint->aiClassification;

        if (! $classification) {
            return response()->json([
                'message' => 'Classification not found.',
            ], 404);
        }

        return response()->json([
            'data' => $classification,
        ]);
    }
}
