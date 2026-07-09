<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TechnicianApplication extends Model
{
    protected $fillable = [
        'application_reference',
        'user_id',
        'full_name',
        'date_of_birth',
        'preferred_service_area_id',
        'phone',
        'address',
        'city',
        'years_experience',
        'highest_qualification',
        'skills',
        'motivation',
        'status',
        'submitted_at',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_notes',
        'rejection_reason',
    ];

    protected $casts = [
        'skills' => 'array',
        'date_of_birth' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function preferredServiceArea(): BelongsTo
    {
        return $this->belongsTo(ServiceArea::class, 'preferred_service_area_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TechnicianApplicationDocument::class);
    }
}
