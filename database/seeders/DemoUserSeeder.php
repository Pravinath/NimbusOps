<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\ServiceArea;
use App\Models\Technician;
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

        $serviceAreas = [
            ['name' => 'Colombo Central', 'city' => 'Colombo', 'zone' => 'Central', 'status' => 'active'],
            ['name' => 'Batticaloa North', 'city' => 'Batticaloa', 'zone' => 'North', 'status' => 'active'],
            ['name' => 'Kandy Metro', 'city' => 'Kandy', 'zone' => 'Metro', 'status' => 'active'],
        ];

        foreach ($serviceAreas as $area) {
            ServiceArea::updateOrCreate(
                ['name' => $area['name']],
                $area
            );
        }

        $customerUser = User::where('email', 'customer@nimbusops.test')->first();

        if ($customerUser) {
            Customer::updateOrCreate(
                ['user_id' => $customerUser->id],
                [
                    'phone' => '0771234567',
                    'address' => 'Demo customer address, Colombo',
                    'city' => 'Colombo',
                    'status' => 'active',
                ]
            );
        }

        $technicianUser = User::where('email', 'technician@nimbusops.test')->first();
        $colomboCentral = ServiceArea::where('name', 'Colombo Central')->first();

        if ($technicianUser) {
            Technician::updateOrCreate(
                ['user_id' => $technicianUser->id],
                [
                    'service_area_id' => $colomboCentral?->id,
                    'skill_category' => 'ac',
                    'availability_status' => 'available',
                    'current_workload' => 0,
                    'performance_score' => 92,
                ]
            );
        }
    }
}
