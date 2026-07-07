<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlaPolicy extends Model
{
    protected $fillable = [
        'priority',
        'resolution_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'resolution_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
