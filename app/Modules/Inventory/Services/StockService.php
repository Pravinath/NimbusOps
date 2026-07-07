<?php

namespace App\Modules\Inventory\Services;

use App\Models\SparePart;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderSparePart;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function __construct(
        private AuditService $auditService,
        private NotificationService $notificationService
    ) {}

    public function createPart(array $data, User $user): SparePart
    {
        return DB::transaction(function () use ($data, $user) {
            $part = SparePart::create($data);

            if ($part->stock_quantity > 0) {
                $part->stockMovements()->create([
                    'user_id' => $user->id,
                    'movement_type' => 'initial_stock',
                    'quantity' => $part->stock_quantity,
                    'quantity_before' => 0,
                    'quantity_after' => $part->stock_quantity,
                    'unit_cost' => $part->unit_cost,
                    'notes' => 'Initial stock recorded.',
                ]);
            }

            $this->auditService->record(
                'spare_part_created',
                $part,
                $user,
                request(),
                ['stock_quantity' => $part->stock_quantity]
            );

            if ($part->stock_quantity <= $part->reorder_level) {
                $this->notificationService->lowStock($part);
            }

            return $part;
        });
    }

    public function adjust(
        SparePart $sparePart,
        User $user,
        string $operation,
        int $quantity,
        string $notes
    ): SparePart {
        return DB::transaction(function () use (
            $sparePart,
            $user,
            $operation,
            $quantity,
            $notes
        ) {
            $sparePart = SparePart::query()
                ->lockForUpdate()
                ->findOrFail($sparePart->id);

            $before = $sparePart->stock_quantity;

            $after = $operation === 'increase'
                ? $before + $quantity
                : $before - $quantity;

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => ['Insufficient stock for this adjustment.'],
                ]);
            }

            $sparePart->update([
                'stock_quantity' => $after,
            ]);

            $sparePart->stockMovements()->create([
                'user_id' => $user->id,
                'movement_type' => $operation === 'increase'
                    ? 'stock_in'
                    : 'manual_decrease',
                'quantity' => $quantity,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'unit_cost' => $sparePart->unit_cost,
                'notes' => $notes,
            ]);

            $this->auditService->record(
                'stock_updated',
                $sparePart,
                $user,
                request(),
                [
                    'operation' => $operation,
                    'quantity' => $quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                ]
            );

            if (
                $after <= $sparePart->reorder_level
                && $before > $sparePart->reorder_level
            ) {
                $this->notificationService->lowStock($sparePart);
            }

            return $sparePart->fresh();
        });
    }

    public function useForWorkOrder(
        WorkOrder $workOrder,
        SparePart $sparePart,
        User $user,
        int $quantity,
        ?string $notes = null
    ): WorkOrderSparePart {
        return DB::transaction(function () use (
            $workOrder,
            $sparePart,
            $user,
            $quantity,
            $notes
        ) {
            $workOrder = WorkOrder::query()
                ->lockForUpdate()
                ->findOrFail($workOrder->id);

            $sparePart = SparePart::query()
                ->lockForUpdate()
                ->findOrFail($sparePart->id);

            $technician = $workOrder->technician;

            if (
                $user->role !== 'admin'
                && $technician->user_id !== $user->id
            ) {
                throw ValidationException::withMessages([
                    'work_order' => [
                        'You are not assigned to this work order.',
                    ],
                ]);
            }

            if (! in_array($workOrder->status, [
                'started',
                'paused',
            ], true)) {
                throw ValidationException::withMessages([
                    'work_order' => [
                        'Spare parts can only be used after work has started.',
                    ],
                ]);
            }

            if ($sparePart->status !== 'active') {
                throw ValidationException::withMessages([
                    'spare_part_id' => ['This spare part is inactive.'],
                ]);
            }

            if ($sparePart->stock_quantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Insufficient spare-part stock.'],
                ]);
            }

            $before = $sparePart->stock_quantity;
            $after = $before - $quantity;
            $totalCost = round(
                (float) $sparePart->unit_cost * $quantity,
                2
            );

            $usage = WorkOrderSparePart::create([
                'work_order_id' => $workOrder->id,
                'spare_part_id' => $sparePart->id,
                'technician_id' => $technician->id,
                'quantity' => $quantity,
                'unit_cost' => $sparePart->unit_cost,
                'total_cost' => $totalCost,
                'notes' => $notes,
                'used_at' => now(),
            ]);

            $sparePart->update([
                'stock_quantity' => $after,
            ]);

            $sparePart->stockMovements()->create([
                'work_order_id' => $workOrder->id,
                'user_id' => $user->id,
                'movement_type' => 'work_order_usage',
                'quantity' => $quantity,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'unit_cost' => $sparePart->unit_cost,
                'notes' => $notes,
            ]);

            $workOrder->updates()->create([
                'user_id' => $user->id,
                'update_type' => 'spare_part_used',
                'notes' => $notes
                    ?? "{$quantity} x {$sparePart->name} used.",
                'metadata' => [
                    'spare_part_id' => $sparePart->id,
                    'quantity' => $quantity,
                    'total_cost' => $totalCost,
                ],
            ]);

            $this->auditService->record(
                'spare_part_used',
                $usage,
                $user,
                request(),
                [
                    'work_order_id' => $workOrder->id,
                    'spare_part_id' => $sparePart->id,
                    'quantity' => $quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                ]
            );

            if (
                $after <= $sparePart->reorder_level
                && $before > $sparePart->reorder_level
            ) {
                $this->notificationService->lowStock($sparePart);
            }

            return $usage->load([
                'sparePart',
                'technician.user',
                'workOrder',
            ]);
        });
    }
}
