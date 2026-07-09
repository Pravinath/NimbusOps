<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianApplicationDocument extends Model
{
    public const TYPES = [
        'identity',
        'qualification',
        'experience',
        'profile_photo',
        'driving_license',
        'police_clearance',
    ];

    protected $fillable = [
        'technician_application_id',
        'document_type',
        'original_name',
        'stored_path',
        'mime_type',
        'file_size',
        'status',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_notes',
    ];

    protected $hidden = [
        'stored_path',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(TechnicianApplication::class, 'technician_application_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
