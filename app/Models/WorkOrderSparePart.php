<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderSparePart extends Model
{
    protected $fillable = [
        'work_order_id',
        'spare_part_id',
        'technician_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'notes',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'used_at' => 'datetime',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }
}
