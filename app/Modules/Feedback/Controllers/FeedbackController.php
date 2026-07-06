<?php

namespace App\Modules\Feedback\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Modules\Feedback\Requests\StoreFeedbackRequest;
use App\Modules\Feedback\Services\FeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class FeedbackController extends Controller
{
    public function __construct(
        private FeedbackService $feedbackService
    ) {
    }

    public function store(
        StoreFeedbackRequest $request
    ): JsonResponse {
        $feedback = $this->feedbackService->submit(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Feedback submitted successfully.',
            'data' => $feedback,
        ], 201);
    }

    public function showByComplaint(
        Complaint $complaint
    ): JsonResponse {
        Gate::authorize('view', $complaint);

        $feedback = $complaint->feedback()
            ->with([
                'customer.user',
                'technician.user',
                'workOrder',
            ])
            ->first();

        if (! $feedback) {
            return response()->json([
                'message' => 'Feedback not found.',
            ], 404);
        }

        return response()->json([
            'data' => $feedback,
        ]);
    }
}