<?php

namespace App\Modules\Technician\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Technician;
use App\Modules\Technician\Requests\StoreTechnicianRequest;
use App\Modules\Technician\Requests\UpdateAvailabilityRequest;
use Illuminate\Http\JsonResponse;

class TechnicianController extends Controller
{
    public function index(): JsonResponse
    {
        $technicians = Technician::with(['user', 'serviceArea'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $technicians,
        ]);
    }

    public function store(StoreTechnicianRequest $request): JsonResponse
    {
        $technician = Technician::create($request->validated());
        $technician->load(['user', 'serviceArea']);

        return response()->json([
            'message' => 'Technician created successfully.',
            'data' => $technician,
        ], 201);
    }

    public function show(Technician $technician): JsonResponse
    {
        $technician->load(['user', 'serviceArea']);

        return response()->json([
            'data' => $technician,
        ]);
    }

    public function updateAvailability(
        UpdateAvailabilityRequest $request,
        Technician $technician
    ): JsonResponse {
        $technician->update($request->validated());

        return response()->json([
            'message' => 'Availability updated successfully.',
            'data' => $technician,
        ]);
    }

    public function workload(Technician $technician): JsonResponse
    {
        return response()->json([
            'technician_id' => $technician->id,
            'current_workload' => $technician->current_workload,
        ]);
    }
}
