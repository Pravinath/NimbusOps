<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkOrder extends Model
{
    protected $fillable = [
        'complaint_id',
        'technician_assignment_id',
        'technician_id',
        'scheduled_visit_time',
        'required_skill',
        'suggested_spare_parts',
        'status',
        'visit_notes',
        'resolution_summary',
        'before_photo_metadata',
        'after_photo_metadata',
        'accepted_at',
        'on_the_way_at',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_visit_time' => 'datetime',
            'suggested_spare_parts' => 'array',
            'before_photo_metadata' => 'array',
            'after_photo_metadata' => 'array',
            'accepted_at' => 'datetime',
            'on_the_way_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(
            TechnicianAssignment::class,
            'technician_assignment_id'
        );
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(WorkOrderUpdate::class)
            ->latest();
    }

    public function sparePartUsages(): HasMany
    {
        return $this->hasMany(WorkOrderSparePart::class);
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(Feedback::class);
    }
}