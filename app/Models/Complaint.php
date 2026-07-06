<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Complaint extends Model
{
    protected $fillable = [
        'customer_id',
        'service_area_id',
        'created_by_user_id',
        'title',
        'description',
        'preferred_visit_time',
        'status',
        'priority',
        'resolved_at',
        'sla_due_at',
        'is_sla_breached',
        'sla_breached_at',
        'sla_escalated_at',
    ];

    protected function casts(): array
    {
        return [
            'preferred_visit_time' => 'datetime',
            'resolved_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'is_sla_breached' => 'boolean',
            'sla_breached_at' => 'datetime',
            'sla_escalated_at' => 'datetime',

        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function serviceArea(): BelongsTo
    {
        return $this->belongsTo(ServiceArea::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(ComplaintTimeline::class)
            ->latest();
    }

    public function aiClassification(): HasOne
    {
        return $this->hasOne(ComplaintAiClassification::class);
    }


    public function technicianAssignments(): HasMany
    {
        return $this->hasMany(TechnicianAssignment::class);
    }

    public function workOrder(): HasOne
    {
        return $this->hasOne(WorkOrder::class);
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(Feedback::class);
    }
}