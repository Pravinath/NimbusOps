<?php

namespace App\Modules\Reporting\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reporting\Services\ReportingService;
use Illuminate\Http\JsonResponse;

class ReportingController extends Controller
{
    public function __construct(
        private ReportingService $reportingService
    ) {
    }

    public function dashboard(): JsonResponse
    {
        return response()->json([
            'data' => $this->reportingService->dashboard(),
        ]);
    }

    public function slaPerformance(): JsonResponse
    {
        return response()->json([
            'data' => $this->reportingService->slaPerformance(),
        ]);
    }

    public function technicianPerformance(): JsonResponse
    {
        return response()->json([
            'data' => $this->reportingService->technicianPerformance(),
        ]);
    }

    public function areaWiseComplaints(): JsonResponse
    {
        return response()->json([
            'data' => $this->reportingService->areaWiseComplaints(),
        ]);
    }

    public function sparePartsUsage(): JsonResponse
    {
        return response()->json([
            'data' => $this->reportingService->sparePartsUsage(),
        ]);
    }

    public function customerSatisfaction(): JsonResponse
    {
        return response()->json([
            'data' => $this->reportingService->customerSatisfaction(),
        ]);
    }

    public function commonIssueCategories(): JsonResponse
    {
        return response()->json([
            'data' => $this->reportingService->commonIssueCategories(),
        ]);
    }

    public function monthlyComplaintTrends(): JsonResponse
    {
        return response()->json([
            'data' => $this->reportingService->monthlyComplaintTrends(),
        ]);
    }
}
