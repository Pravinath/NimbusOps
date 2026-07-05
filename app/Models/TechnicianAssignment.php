<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TechnicianAssignment extends Model
{
    protected $fillable = [
        'complaint_id',
        'technician_id',
        'assigned_by_user_id',
        'status',
        'is_override',
        'notes',
        'assigned_at',
        'unassigned_at',
    ];

    protected function casts(): array
    {
        return [
            'is_override' => 'boolean',
            'assigned_at' => 'datetime',
            'unassigned_at' => 'datetime',
        ];
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function workOrder(): HasOne
    {
        return $this->hasOne(WorkOrder::class);
    }
}