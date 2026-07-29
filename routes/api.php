<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API Controllers
use App\Http\Controllers\Api\CaseController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\InspectionController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\ComponentController;

// Inspector Controllers
use App\Http\Controllers\Inspector\DashboardController as InspectorDashboardController;
use App\Http\Controllers\Inspector\AgendaController as InspectorAgendaController;
use App\Http\Controllers\Inspector\InspectionController as InspectorInspectionController;

// Admin Controllers
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\CaseManagementController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes
Route::post('/vin/validate', [VehicleController::class, 'validateVin']);
Route::post('/vehicles/decode-vin', [VehicleController::class, 'decodeVinPublic'])->name('api.vehicles.decode-vin');
Route::get('/components/types', [ComponentController::class, 'types']);
Route::get('/components/conditions', [ComponentController::class, 'conditions']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {

    // Current user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    /*
    |--------------------------------------------------------------------------
    | Case Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('cases')->group(function () {
        Route::get('/', [CaseController::class, 'index']);
        Route::post('/', [CaseController::class, 'store']);
        Route::get('/stats', [CaseController::class, 'stats']);
        Route::get('/search', [CaseController::class, 'search']);
        Route::get('/{case}', [CaseController::class, 'show']);
        Route::put('/{case}', [CaseController::class, 'update']);
        Route::delete('/{case}', [CaseController::class, 'destroy']);
        Route::post('/{case}/submit', [CaseController::class, 'submit']);
        Route::post('/{case}/cancel', [CaseController::class, 'cancel']);
        Route::get('/{case}/timeline', [CaseController::class, 'timeline']);
        Route::post('/{case}/decode-vin', [CaseController::class, 'decodeVin']);

        // Staff only routes
        Route::middleware('role.staff')->group(function () {
            Route::post('/{case}/assign-inspector', [CaseController::class, 'assignInspector']);
            Route::put('/{case}/status', [CaseController::class, 'updateStatus']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Document Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('cases/{case}/documents')->group(function () {
        Route::get('/', [DocumentController::class, 'index']);
        Route::post('/', [DocumentController::class, 'store']);
        Route::get('/pending', [DocumentController::class, 'pending']);
        Route::delete('/{document}', [DocumentController::class, 'destroy']);
        Route::get('/{document}/download', [DocumentController::class, 'download']);

        // Staff only routes
        Route::middleware('role.staff')->group(function () {
            Route::post('/{document}/approve', [DocumentController::class, 'approve']);
            Route::post('/{document}/reject', [DocumentController::class, 'reject']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Vehicle Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('cases/{case}/vehicle')->group(function () {
        Route::get('/', [VehicleController::class, 'show']);
        Route::put('/', [VehicleController::class, 'update']);
        Route::post('/decode-vin', [VehicleController::class, 'decodeVin']);
    });

    /*
    |--------------------------------------------------------------------------
    | Component Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('cases/{case}/components')->group(function () {
        Route::get('/', [ComponentController::class, 'index']);
        Route::post('/', [ComponentController::class, 'store']);
        Route::put('/{component}', [ComponentController::class, 'update']);
        Route::delete('/{component}', [ComponentController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Appointment Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('appointments')->group(function () {
        Route::get('/today', [AppointmentController::class, 'today']);
        Route::get('/available-dates', [AppointmentController::class, 'availableDates']);
        Route::get('/available-slots', [AppointmentController::class, 'availableSlots']);
    });

    Route::prefix('cases/{case}/appointment')->group(function () {
        Route::get('/', [AppointmentController::class, 'show']);
        Route::post('/', [AppointmentController::class, 'schedule']);
        Route::put('/', [AppointmentController::class, 'reschedule']);
        Route::delete('/', [AppointmentController::class, 'cancel']);
        Route::post('/confirm', [AppointmentController::class, 'confirm']);
    });

    /*
    |--------------------------------------------------------------------------
    | Inspection Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('inspections')->group(function () {
        Route::get('/{inspection}', [InspectionController::class, 'show']);
        Route::put('/{inspection}', [InspectionController::class, 'update']);
        Route::post('/{inspection}/photos', [InspectionController::class, 'uploadPhoto']);
        Route::post('/{inspection}/verify-component', [InspectionController::class, 'verifyComponent']);
        Route::post('/{inspection}/complete', [InspectionController::class, 'complete']);
        Route::post('/{inspection}/sign', [InspectionController::class, 'sign']);
        Route::post('/{inspection}/generate-documents', [InspectionController::class, 'generateDocuments']);
    });

    Route::prefix('cases/{case}/inspection')->group(function () {
        Route::post('/start', [InspectionController::class, 'start']);
    });

    /*
    |--------------------------------------------------------------------------
    | Inspector Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('inspector')->middleware('role.inspector')->group(function () {
        // Dashboard
        Route::get('/dashboard', [InspectorDashboardController::class, 'index']);
        Route::get('/stats', [InspectorDashboardController::class, 'stats']);

        // Agenda
        Route::get('/agenda', [InspectorAgendaController::class, 'index']);
        Route::get('/agenda/week', [InspectorAgendaController::class, 'week']);
        Route::get('/agenda/today', [InspectorAgendaController::class, 'today']);
        Route::get('/agenda/upcoming', [InspectorAgendaController::class, 'upcoming']);
        Route::get('/appointments/{appointment}', [InspectorAgendaController::class, 'show']);

        // Inspection workflow
        Route::get('/appointments/{appointment}/checklist', [InspectorInspectionController::class, 'checklist']);
        Route::get('/inspections/{inspection}/photos', [InspectorInspectionController::class, 'photos']);
        Route::get('/inspections/{inspection}/components', [InspectorInspectionController::class, 'components']);
        Route::get('/inspections/{inspection}/summary', [InspectorInspectionController::class, 'summary']);
        Route::get('/inspections/{inspection}/documents', [InspectorInspectionController::class, 'documents']);
        Route::get('/inspections/{inspection}/print', [InspectorInspectionController::class, 'print']);

        // History
        Route::get('/history', [InspectorInspectionController::class, 'history']);
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->middleware('role.staff')->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);
        Route::get('/stats', [AdminDashboardController::class, 'stats']);
        Route::get('/activity', [AdminDashboardController::class, 'activity']);

        // Case Management
        Route::get('/cases', [CaseManagementController::class, 'index']);
        Route::get('/cases/export', [CaseManagementController::class, 'export']);
        Route::get('/cases/{case}', [CaseManagementController::class, 'show']);
        Route::put('/cases/{case}/status', [CaseManagementController::class, 'updateStatus']);
        Route::post('/cases/{case}/assign-inspector', [CaseManagementController::class, 'assignInspector']);
        Route::post('/cases/{case}/notes', [CaseManagementController::class, 'addNote']);
        Route::post('/cases/bulk-update', [CaseManagementController::class, 'bulkUpdate']);

        // Reports
        Route::get('/reports', [ReportController::class, 'index']);
        Route::get('/reports/revenue', [ReportController::class, 'revenue']);
        Route::get('/reports/cases', [ReportController::class, 'cases']);
        Route::get('/reports/inspections', [ReportController::class, 'inspections']);
        Route::get('/reports/inspector-performance', [ReportController::class, 'inspectorPerformance']);
        Route::get('/reports/customers', [ReportController::class, 'customers']);

        // Settings (Admin only for write operations)
        Route::get('/settings', [SettingsController::class, 'index']);
        Route::get('/settings/{key}', [SettingsController::class, 'show']);
        Route::get('/settings/pricing', [SettingsController::class, 'pricing']);
        Route::get('/settings/notifications', [SettingsController::class, 'notifications']);

        // Admin only settings updates
        Route::middleware('role.admin')->group(function () {
            Route::put('/settings', [SettingsController::class, 'update']);
            Route::put('/settings/pricing', [SettingsController::class, 'updatePricing']);
            Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications']);

            // User Management
            Route::get('/users', [SettingsController::class, 'users']);
            Route::post('/users', [SettingsController::class, 'createUser']);
            Route::put('/users/{targetUser}', [SettingsController::class, 'updateUser']);
            Route::delete('/users/{targetUser}', [SettingsController::class, 'deleteUser']);
        });
    });
});
