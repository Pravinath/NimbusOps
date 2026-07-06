<?php

namespace Database\Seeders;

use App\Models\SlaPolicy;
use Illuminate\Database\Seeder;

class SlaPolicySeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            'critical' => 120,
            'high' => 240,
            'medium' => 720,
            'low' => 1440,
        ];

        foreach ($policies as $priority => $minutes) {
            SlaPolicy::updateOrCreate(
                ['priority' => $priority],
                [
                    'resolution_minutes' => $minutes,
                    'is_active' => true,
                ]
            );
        }
    }
}