<?php

use App\Modules\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Modules\Customer\Controllers\CustomerController;
use App\Modules\ServiceArea\Controllers\ServiceAreaController;
use App\Modules\Technician\Controllers\TechnicianController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/phase-one-health', function () {
        return response()->json([
            'project' => 'NimbusOps',
            'phase' => 'Phase 1',
            'status' => 'API authentication ready',
        ]);
    });

    Route::middleware('role:admin,agent,dispatcher,supervisor')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index']);
        Route::get('/customers/{customer}', [CustomerController::class, 'show']);
    });

    Route::middleware('role:admin,agent')->group(function () {
        Route::post('/customers', [CustomerController::class, 'store']);
    });

    Route::middleware('role:admin,dispatcher,supervisor')->group(function () {
        Route::get('/service-areas', [ServiceAreaController::class, 'index']);
        Route::get('/service-areas/{serviceArea}', [ServiceAreaController::class, 'show']);

        Route::get('/technicians', [TechnicianController::class, 'index']);
        Route::get('/technicians/{technician}', [TechnicianController::class, 'show']);
        Route::get('/technicians/{technician}/workload', [
            TechnicianController::class,
            'workload',
        ]);
    });

    Route::middleware('role:admin')->group(function () {
        Route::post('/service-areas', [ServiceAreaController::class, 'store']);
        Route::post('/technicians', [TechnicianController::class, 'store']);
    });

    Route::middleware('role:admin,technician')->group(function () {
        Route::patch('/technicians/{technician}/availability', [
            TechnicianController::class,
            'updateAvailability',
        ]);
    });
});