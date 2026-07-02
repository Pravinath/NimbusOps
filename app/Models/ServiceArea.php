<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceArea extends Model
{
    protected $fillable = [
        'name',
        'city',
        'zone',
        'status',
    ];

    public function technicians(): HasMany
    {
        return $this->hasMany(Technician::class);
    }
}