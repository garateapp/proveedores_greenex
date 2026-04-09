<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AsistenciaQrController;
use App\Http\Controllers\Api\UbicacionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the Application and given a URI prefix of "/api".
|
*/

// Public ubicaciones routes for mobile app
Route::prefix('v1')->group(function () {
    // Public routes - no authentication required
    Route::get('/ubicaciones', [UbicacionController::class, 'index']);
    Route::get('/ubicaciones/principales', [UbicacionController::class, 'principales']);
    Route::get('/ubicaciones/{ubicacion}', [UbicacionController::class, 'show']);
    Route::post('/ubicaciones', [UbicacionController::class, 'store']);
    Route::put('/ubicaciones/{ubicacion}', [UbicacionController::class, 'update']);
    Route::delete('/ubicaciones/{ubicacion}', [UbicacionController::class, 'destroy']);

    // Registro de asistencia por QR de tarjeta
    Route::post('/asistencias/qr', [AsistenciaQrController::class, 'store']);

    // Activity logs - authenticated routes
    Route::middleware('auth')->group(function () {
        Route::get('/activity-logs', [ActivityLogController::class, 'index']);
        Route::get('/activity-logs/summary', [ActivityLogController::class, 'summary']);
        Route::get('/activity-logs/navigation-history', [ActivityLogController::class, 'navigationHistory']);
        Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show']);
    });
});
