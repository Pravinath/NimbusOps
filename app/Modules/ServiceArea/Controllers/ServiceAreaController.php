<?php

namespace App\Modules\ServiceArea\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ServiceArea;
use App\Modules\ServiceArea\Requests\StoreServiceAreaRequest;
use Illuminate\Http\JsonResponse;

class ServiceAreaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => ServiceArea::latest()->get(),
        ]);
    }

    public function store(StoreServiceAreaRequest $request): JsonResponse
    {
        $serviceArea = ServiceArea::create($request->validated());

        return response()->json([
            'message' => 'Service area created successfully.',
            'data' => $serviceArea,
        ], 201);
    }

    public function show(ServiceArea $serviceArea): JsonResponse
    {
        $serviceArea->load('technicians.user');

        return response()->json([
            'data' => $serviceArea,
        ]);
    }
}
