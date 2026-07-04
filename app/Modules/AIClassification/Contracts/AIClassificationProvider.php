<?php

namespace App\Modules\AIClassification\Contracts;

use App\Models\Complaint;

interface AIClassificationProvider
{
    public function classify(Complaint $complaint): array;

    public function name(): string;
}