<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrderUpdate extends Model
{
    protected $fillable = [
        'work_order_id',
        'user_id',
        'update_type',
        'from_status',
        'to_status',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(WorkOrderUpdate::class)
            ->latest();
    }
}
