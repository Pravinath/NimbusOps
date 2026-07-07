<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Agent = 'agent';
    case Dispatcher = 'dispatcher';
    case Technician = 'technician';
    case Inventory = 'inventory';
    case Supervisor = 'supervisor';
    case Admin = 'admin';
}
