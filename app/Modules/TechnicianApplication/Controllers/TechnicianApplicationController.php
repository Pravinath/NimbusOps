<?php

namespace App\Modules\TechnicianApplication\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TechnicianApplication;
use App\Models\TechnicianApplicationDocument;
use App\Modules\TechnicianApplication\Requests\StoreTechnicianApplicationRequest;
use App\Modules\TechnicianApplication\Requests\UploadTechnicianApplicationDocumentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TechnicianApplicationController extends Controller
{
    public function store(StoreTechnicianApplicationRequest $request): JsonResponse
    {
        if ($request->user()->technicianApplication()->exists()) {
            return response()->json([
                'message' => 'You have already submitted a technician application.',
            ], 409);
        }

        $application = TechnicianApplication::create([
            'application_reference' => $this->newReference(),
            'user_id' => $request->user()->id,
            'full_name' => $request->string('full_name'),
            'date_of_birth' => $request->date('date_of_birth'),
            'preferred_service_area_id' => $request->integer('preferred_service_area_id') ?: null,
            'phone' => $request->string('phone'),
            'address' => $request->string('address'),
            'city' => $request->string('city'),
            'years_experience' => $request->integer('years_experience'),
            'highest_qualification' => $request->string('highest_qualification'),
            'skills' => $request->validated('skills'),
            'motivation' => $request->string('motivation'),
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Technician application submitted successfully.',
            'application' => $application->load('preferredServiceArea', 'documents'),
        ], 201);
    }

    public function mine(Request $request): JsonResponse
    {
        $application = $request->user()
            ->technicianApplication()
            ->with('preferredServiceArea', 'documents')
            ->firstOrFail();

        return response()->json([
            'data' => $application,
        ]);
    }

    public function documents(Request $request): JsonResponse
    {
        $application = $this->applicationFor($request);

        return response()->json([
            'data' => $application->documents()->latest()->get(),
        ]);
    }

    public function uploadDocument(UploadTechnicianApplicationDocumentRequest $request): JsonResponse
    {
        $application = $this->applicationFor($request);
        $file = $request->file('document');
        $documentType = $request->string('document_type')->toString();
        $existing = $application->documents()
            ->where('document_type', $documentType)
            ->first();

        if ($existing?->status === 'verified') {
            return response()->json([
                'message' => 'Verified documents cannot be replaced.',
            ], 409);
        }

        if ($existing) {
            Storage::disk('local')->delete($existing->stored_path);
        }

        $extension = $file->getClientOriginalExtension();
        $fileName = $documentType.'-'.Str::uuid().'.'.$extension;
        $storedPath = $file->storeAs(
            'technician-applications/'.$application->id,
            $fileName,
            'local'
        );

        $document = $application->documents()->updateOrCreate(
            ['document_type' => $documentType],
            [
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $storedPath,
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'file_size' => $file->getSize(),
                'status' => 'uploaded',
                'reviewed_by_user_id' => null,
                'reviewed_at' => null,
                'review_notes' => null,
            ]
        );

        return response()->json([
            'message' => 'Document uploaded successfully.',
            'document' => $document,
        ], $existing ? 200 : 201);
    }

    public function deleteDocument(Request $request, TechnicianApplicationDocument $document): JsonResponse
    {
        $application = $this->applicationFor($request);

        if ($document->technician_application_id !== $application->id) {
            abort(404);
        }

        if ($document->status === 'verified') {
            return response()->json([
                'message' => 'Verified documents cannot be deleted.',
            ], 409);
        }

        Storage::disk('local')->delete($document->stored_path);
        $document->delete();

        return response()->json([
            'message' => 'Document deleted successfully.',
        ]);
    }

    private function applicationFor(Request $request): TechnicianApplication
    {
        return $request->user()
            ->technicianApplication()
            ->firstOrFail();
    }

    private function newReference(): string
    {
        do {
            $reference = 'TECH-'.now()->format('Ym').'-'.Str::upper(Str::random(8));
        } while (TechnicianApplication::where('application_reference', $reference)->exists());

        return $reference;
    }
}