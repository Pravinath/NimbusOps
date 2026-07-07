<?php

use App\Modules\AIClassification\Controllers\AIClassificationController;
use App\Modules\Audit\Controllers\AuditLogController;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Complaint\Controllers\ComplaintController;
use App\Modules\Customer\Controllers\CustomerController;
use App\Modules\Dispatch\Controllers\DispatchController;
use App\Modules\Feedback\Controllers\FeedbackController;
use App\Modules\Inventory\Controllers\InventoryController;
use App\Modules\Notification\Controllers\NotificationController;
use App\Modules\Reporting\Controllers\ReportingController;
use App\Modules\ServiceArea\Controllers\ServiceAreaController;
use App\Modules\Technician\Controllers\TechnicianController;
use App\Modules\WorkOrder\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

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

    Route::middleware('role:customer,agent,dispatcher,supervisor,admin')
        ->group(function () {
            Route::get('/complaints', [ComplaintController::class, 'index']);
            Route::get('/complaints/{complaint}', [ComplaintController::class, 'show']);
            Route::get('/complaints/{complaint}/timeline', [
                ComplaintController::class,
                'timeline',
            ]);
        });

    Route::middleware('role:customer,agent,admin')
        ->post('/complaints', [ComplaintController::class, 'store']);

    Route::middleware('role:agent,dispatcher,supervisor,admin')
        ->patch('/complaints/{complaint}/status', [
            ComplaintController::class,
            'updateStatus',
        ]);

    Route::middleware([
        'role:agent,dispatcher,supervisor,admin',
        'throttle:10,1',
    ])->post('/complaints/{complaint}/ai-classify', [
        AIClassificationController::class,
        'classify',
    ]);

    Route::middleware('role:customer,agent,dispatcher,supervisor,admin')
        ->get('/complaints/{complaint}/ai-classification', [
            AIClassificationController::class,
            'show',
        ]);

    Route::middleware('role:dispatcher,supervisor,admin')
        ->get('/complaints/{complaint}/suggest-technicians', [
            DispatchController::class,
            'suggestions',
        ]);

    Route::middleware('role:dispatcher,admin')
        ->post('/complaints/{complaint}/assign-technician', [
            DispatchController::class,
            'assign',
        ]);

    Route::middleware('role:technician,dispatcher,supervisor,admin')
        ->group(function () {
            Route::get('/work-orders', [
                WorkOrderController::class,
                'index',
            ]);

            Route::get('/work-orders/{workOrder}', [
                WorkOrderController::class,
                'show',
            ]);
        });

    Route::middleware('role:technician,admin')
        ->group(function () {
            Route::patch('/work-orders/{workOrder}/accept', [
                WorkOrderController::class,
                'accept',
            ]);

            Route::patch('/work-orders/{workOrder}/on-the-way', [
                WorkOrderController::class,
                'onTheWay',
            ]);

            Route::patch('/work-orders/{workOrder}/start', [
                WorkOrderController::class,
                'start',
            ]);

            Route::patch('/work-orders/{workOrder}/pause', [
                WorkOrderController::class,
                'pause',
            ]);

            Route::patch('/work-orders/{workOrder}/complete', [
                WorkOrderController::class,
                'complete',
            ]);

            Route::post('/work-orders/{workOrder}/updates', [
                WorkOrderController::class,
                'addUpdate',
            ]);
        });

    Route::middleware(
        'role:technician,inventory,dispatcher,supervisor,admin'
    )->group(function () {
        Route::get('/spare-parts', [
            InventoryController::class,
            'index',
        ]);

        Route::get('/spare-parts/{sparePart}', [
            InventoryController::class,
            'show',
        ]);
    });

    Route::middleware('role:inventory,admin')
        ->group(function () {
            Route::post('/spare-parts', [
                InventoryController::class,
                'store',
            ]);

            Route::patch('/spare-parts/{sparePart}', [
                InventoryController::class,
                'update',
            ]);

            Route::patch('/spare-parts/{sparePart}/stock', [
                InventoryController::class,
                'adjust',
            ]);
        });

    Route::middleware('role:inventory,supervisor,admin')
        ->group(function () {
            Route::get('/inventory/low-stock', [
                InventoryController::class,
                'lowStock',
            ]);

            Route::get('/stock-movements', [
                InventoryController::class,
                'movements',
            ]);
        });

    Route::middleware('role:technician,admin')
        ->post('/work-orders/{workOrder}/use-spare-part', [
            InventoryController::class,
            'useSparePart',
        ]);

    Route::middleware('role:customer')
        ->post('/feedback', [
            FeedbackController::class,
            'store',
        ]);

    Route::middleware('role:customer,supervisor,admin')
        ->get('/feedback/complaint/{complaint}', [
            FeedbackController::class,
            'showByComplaint',
        ]);

    Route::middleware('role:admin')
        ->get('/audit-logs', [
            AuditLogController::class,
            'index',
        ]);

    Route::get('/notifications', [
        NotificationController::class,
        'index',
    ]);

    Route::patch('/notifications/{notification}/read', [
        NotificationController::class,
        'markRead',
    ]);

    Route::middleware('role:supervisor,admin')->group(function () {
        Route::get('/admin/dashboard', [
            ReportingController::class,
            'dashboard',
        ]);

        Route::get('/reports/sla-performance', [
            ReportingController::class,
            'slaPerformance',
        ]);

        Route::get('/reports/customer-satisfaction', [
            ReportingController::class,
            'customerSatisfaction',
        ]);

        Route::get('/reports/monthly-complaint-trends', [
            ReportingController::class,
            'monthlyComplaintTrends',
        ]);
    });

    Route::middleware('role:dispatcher,supervisor,admin')->group(function () {
        Route::get('/reports/technician-performance', [
            ReportingController::class,
            'technicianPerformance',
        ]);

        Route::get('/reports/area-wise-complaints', [
            ReportingController::class,
            'areaWiseComplaints',
        ]);

        Route::get('/reports/common-issue-categories', [
            ReportingController::class,
            'commonIssueCategories',
        ]);
    });

    Route::middleware('role:inventory,supervisor,admin')
        ->get('/reports/spare-parts-usage', [
            ReportingController::class,
            'sparePartsUsage',
        ]);

});
