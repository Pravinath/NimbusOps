<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintAiClassification extends Model
{
    protected $fillable = [
        'complaint_id',
        'provider',
        'issue_category',
        'predicted_priority',
        'suggested_skill',
        'suggested_spare_parts',
        'suggested_sla_minutes',
        'repeated_complaint_risk',
        'summary',
        'confidence_score',
        'raw_response',
        'classified_at',
    ];

    protected function casts(): array
    {
        return [
            'suggested_spare_parts' => 'array',
            'repeated_complaint_risk' => 'boolean',
            'confidence_score' => 'decimal:2',
            'raw_response' => 'array',
            'classified_at' => 'datetime',
        ];
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }
}
