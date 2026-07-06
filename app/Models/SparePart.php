<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SparePart extends Model
{
    protected $fillable = [
        'sku',
        'name',
        'description',
        'stock_quantity',
        'reorder_level',
        'unit_cost',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'stock_quantity' => 'integer',
            'reorder_level' => 'integer',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function workOrderUsages(): HasMany
    {
        return $this->hasMany(WorkOrderSparePart::class);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn(
            'stock_quantity',
            '<=',
            'reorder_level'
        );
    }
}