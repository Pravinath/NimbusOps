<?php

namespace App\Modules\TechnicianApplication\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Technician;
use App\Models\TechnicianApplication;
use App\Models\TechnicianApplicationDocument;
use App\Modules\TechnicianApplication\Requests\ReviewTechnicianApplicationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminTechnicianApplicationController extends Controller
{
    public function index(): JsonResponse
    {
        $applications = TechnicianApplication::query()
            ->with(['user:id,name,email,role,status', 'preferredServiceArea', 'documents'])
            ->latest('submitted_at')
            ->get();

        return response()->json([
            'data' => $applications,
        ]);
    }

    public function show(TechnicianApplication $technicianApplication): JsonResponse
    {
        return response()->json([
            'data' => $technicianApplication->load([
                'user:id,name,email,role,status',
                'preferredServiceArea',
                'documents',
                'reviewedBy:id,name,email,role',
            ]),
        ]);
    }

    public function viewDocument(
        TechnicianApplication $technicianApplication,
        TechnicianApplicationDocument $document
    ) {
        if ($document->technician_application_id !== $technicianApplication->id) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($document->stored_path)) {
            abort(404, 'Document file was not found.');
        }

        return response()->file(Storage::disk('local')->path($document->stored_path), [
            'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($document->original_name).'"',
        ]);
    }

    public function updateStatus(
        ReviewTechnicianApplicationRequest $request,
        TechnicianApplication $technicianApplication
    ): JsonResponse {
        if ($technicianApplication->status === 'approved') {
            return response()->json([
                'message' => 'Approved applications cannot be moved back through review status.',
            ], 409);
        }

        $status = $request->string('status')->toString();

        $technicianApplication->update([
            'status' => $status,
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $request->input('review_notes'),
            'rejection_reason' => $status === 'rejected'
                ? $request->input('rejection_reason')
                : null,
        ]);

        return response()->json([
            'message' => 'Technician application status updated.',
            'application' => $technicianApplication->fresh()->load([
                'user:id,name,email,role,status',
                'preferredServiceArea',
                'documents',
                'reviewedBy:id,name,email,role',
            ]),
        ]);
    }

    public function approve(TechnicianApplication $technicianApplication): JsonResponse
    {
        if ($technicianApplication->status === 'approved') {
            return response()->json([
                'message' => 'This technician application is already approved.',
            ], 409);
        }

        if ($technicianApplication->status === 'rejected') {
            return response()->json([
                'message' => 'Rejected applications cannot be approved without reopening review first.',
            ], 409);
        }

        $documentCount = $technicianApplication->documents()->count();

        if ($documentCount === 0) {
            return response()->json([
                'message' => 'At least one application document is required before approval.',
            ], 422);
        }

        $application = DB::transaction(function () use ($technicianApplication) {
            $technicianApplication->load('user');
            $primarySkill = $technicianApplication->skills[0] ?? 'general';

            $technicianApplication->user->update([
                'role' => 'technician',
                'status' => 'active',
            ]);

            Technician::updateOrCreate(
                ['user_id' => $technicianApplication->user_id],
                [
                    'service_area_id' => $technicianApplication->preferred_service_area_id,
                    'skill_category' => $primarySkill,
                    'availability_status' => 'available',
                ]
            );

            $technicianApplication->update([
                'status' => 'approved',
                'reviewed_by_user_id' => request()->user()->id,
                'reviewed_at' => now(),
                'review_notes' => 'Approved for technician activation.',
                'rejection_reason' => null,
            ]);

            return $technicianApplication->fresh()->load([
                'user:id,name,email,role,status',
                'preferredServiceArea',
                'documents',
                'reviewedBy:id,name,email,role',
            ]);
        });

        return response()->json([
            'message' => 'Technician application approved and technician account activated.',
            'application' => $application,
        ]);
    }
}
