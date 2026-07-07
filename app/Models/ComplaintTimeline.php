<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintTimeline extends Model
{
    protected $fillable = [
        'complaint_id',
        'user_id',
        'event_type',
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

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
