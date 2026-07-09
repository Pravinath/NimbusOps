<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Agent = 'agent';
    case Dispatcher = 'dispatcher';
    case Technician = 'technician';
    case TechnicianApplicant = 'technician_applicant';
    case Inventory = 'inventory';
    case Supervisor = 'supervisor';
    case Admin = 'admin';
}
