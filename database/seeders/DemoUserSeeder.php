<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['Customer User', 'customer@nimbusops.test', 'customer'],
            ['Call Center Agent', 'agent@nimbusops.test', 'agent'],
            ['Dispatcher User', 'dispatcher@nimbusops.test', 'dispatcher'],
            ['Technician User', 'technician@nimbusops.test', 'technician'],
            ['Inventory Manager', 'inventory@nimbusops.test', 'inventory'],
            ['Supervisor User', 'supervisor@nimbusops.test', 'supervisor'],
            ['Admin User', 'admin@nimbusops.test', 'admin'],
        ];

        foreach ($users as [$name, $email, $role]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => 'password123',
                    'role' => $role,
                    'status' => 'active',
                ]
            );
        }
    }
}