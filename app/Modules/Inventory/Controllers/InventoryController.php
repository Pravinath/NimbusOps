<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SparePart;
use App\Models\StockMovement;
use App\Models\WorkOrder;
use App\Modules\Inventory\Requests\AdjustStockRequest;
use App\Modules\Inventory\Requests\StoreSparePartRequest;
use App\Modules\Inventory\Requests\UpdateSparePartRequest;
use App\Modules\Inventory\Requests\UseSparePartRequest;
use App\Modules\Inventory\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class InventoryController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => SparePart::latest()->get(),
        ]);
    }

    public function store(
        StoreSparePartRequest $request
    ): JsonResponse {
        $part = $this->stockService->createPart(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'message' => 'Spare part created successfully.',
            'data' => $part,
        ], 201);
    }

    public function show(SparePart $sparePart): JsonResponse
    {
        return response()->json([
            'data' => $sparePart->load([
                'stockMovements' => fn ($query) => $query->latest(),
            ]),
        ]);
    }

    public function update(
        UpdateSparePartRequest $request,
        SparePart $sparePart
    ): JsonResponse {
        $sparePart->update($request->validated());

        return response()->json([
            'message' => 'Spare part updated successfully.',
            'data' => $sparePart->fresh(),
        ]);
    }

    public function adjust(
        AdjustStockRequest $request,
        SparePart $sparePart
    ): JsonResponse {
        $part = $this->stockService->adjust(
            $sparePart,
            $request->user(),
            $request->validated('operation'),
            $request->integer('quantity'),
            $request->validated('notes')
        );

        return response()->json([
            'message' => 'Stock adjusted successfully.',
            'data' => $part,
        ]);
    }

    public function lowStock(): JsonResponse
    {
        return response()->json([
            'data' => SparePart::query()
                ->where('status', 'active')
                ->lowStock()
                ->orderBy('stock_quantity')
                ->get(),
        ]);
    }

    public function movements(): JsonResponse
    {
        return response()->json([
            'data' => StockMovement::with([
                'sparePart',
                'workOrder',
                'user',
            ])->latest()->get(),
        ]);
    }

    public function useSparePart(
        UseSparePartRequest $request,
        WorkOrder $workOrder
    ): JsonResponse {
        Gate::authorize('update', $workOrder);

        $sparePart = SparePart::findOrFail(
            $request->validated('spare_part_id')
        );

        $usage = $this->stockService->useForWorkOrder(
            $workOrder,
            $sparePart,
            $request->user(),
            $request->integer('quantity'),
            $request->validated('notes')
        );

        return response()->json([
            'message' => 'Spare part usage recorded successfully.',
            'data' => $usage,
        ], 201);
    }
}