<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\PlantillaDocumentoTrabajadorController;
use App\Http\Controllers\CentroCargaController;
use App\Http\Controllers\ContratistaDashboardController;
use App\Http\Controllers\ContratistaRegistrationController;
use App\Http\Controllers\CuadraturaAsistenciaController;
use App\Http\Controllers\DocumentoTrabajadorFirmaController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Registro público de contratistas
Route::middleware('guest')->group(function () {
    Route::get('contratistas/registro', [ContratistaRegistrationController::class, 'create'])->name('contratistas.registro');
    Route::post('contratistas/registro', [ContratistaRegistrationController::class, 'store']);
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard dinámico según rol
    Route::get('dashboard', [ContratistaDashboardController::class, 'index'])->name('dashboard');

    // Centro de carga documental de trabajadores
    Route::get('centro-carga', [CentroCargaController::class, 'index'])->name('centro-carga.index');
    Route::get('centro-carga/trabajadores', [CentroCargaController::class, 'searchTrabajadores'])->name('centro-carga.trabajadores.search');
    Route::get('centro-carga/trabajadores/{trabajador}/requerimientos', [CentroCargaController::class, 'requirements'])->name('centro-carga.trabajadores.requirements');
    Route::post('centro-carga/documentos', [\App\Http\Controllers\DocumentoTrabajadorController::class, 'storeFromCentroCarga'])->name('centro-carga.documentos.store');

    // Centro de carga documental de contratistas
    Route::get('centro-carga-contratistas', [CentroCargaController::class, 'contratistasIndex'])->name('centro-carga-contratistas.index');
    Route::get('centro-carga-contratistas/contratistas', [CentroCargaController::class, 'searchContratistas'])->name('centro-carga-contratistas.contratistas.search');
    Route::get('centro-carga-contratistas/contratistas/{contratista}/requerimientos', [CentroCargaController::class, 'contratistaRequirements'])->name('centro-carga-contratistas.contratistas.requirements');
    Route::post('centro-carga-contratistas/documentos', [\App\Http\Controllers\DocumentoController::class, 'storeFromCentroCargaContratista'])->name('centro-carga-contratistas.documentos.store');

    // Gestión de trabajadores
    Route::resource('trabajadores', \App\Http\Controllers\TrabajadorController::class);
    Route::post('trabajadores/import', [\App\Http\Controllers\TrabajadorImportController::class, 'import'])->name('trabajadores.import');
    Route::get('trabajadores/template/download', [\App\Http\Controllers\TrabajadorImportController::class, 'downloadTemplate'])->name('trabajadores.template');
    Route::patch('trabajadores/{trabajador}/estado', [\App\Http\Controllers\TrabajadorController::class, 'toggleEstado'])->name('trabajadores.toggle-estado');
    Route::post('trabajadores/{trabajador}/documentos', [\App\Http\Controllers\DocumentoTrabajadorController::class, 'store'])->name('trabajadores.documentos.store');
    Route::get('trabajadores/{trabajador}/firmas-documentos', [DocumentoTrabajadorFirmaController::class, 'index'])->name('trabajadores.firmas.index');
    Route::get('trabajadores/{trabajador}/firmas-documentos/{plantillaDocumentoTrabajador}/create', [DocumentoTrabajadorFirmaController::class, 'create'])->name('trabajadores.firmas.create');
    Route::post('trabajadores/{trabajador}/firmas-documentos/{plantillaDocumentoTrabajador}', [DocumentoTrabajadorFirmaController::class, 'store'])->name('trabajadores.firmas.store');
    Route::get('documentos-trabajadores/{documentoTrabajador}/preview', [\App\Http\Controllers\DocumentoTrabajadorController::class, 'preview'])->name('documentos-trabajadores.preview');
    Route::get('documentos-trabajadores/{documentoTrabajador}/download', [\App\Http\Controllers\DocumentoTrabajadorController::class, 'download'])->name('documentos-trabajadores.download');

    // Gestión de faenas
    Route::resource('faenas', \App\Http\Controllers\FaenaController::class);
    Route::post('faenas/{faena}/trabajadores', [\App\Http\Controllers\FaenaController::class, 'assignTrabajador'])->name('faenas.assign');
    Route::delete('faenas/{faena}/trabajadores/{trabajador}', [\App\Http\Controllers\FaenaController::class, 'unassignTrabajador'])->name('faenas.unassign');
    Route::post('faenas/{faena}/contratistas', [\App\Http\Controllers\FaenaController::class, 'storeContratista'])->name('faenas.contratistas.store');
    Route::delete('faenas/{faena}/contratistas/{contratista}', [\App\Http\Controllers\FaenaController::class, 'destroyContratista'])->name('faenas.contratistas.destroy');

    // Gestión de documentos
    Route::resource('documentos', \App\Http\Controllers\DocumentoController::class)->only(['index', 'create', 'store']);
    Route::get('documentos/aprobaciones', [\App\Http\Controllers\DocumentoController::class, 'approvals'])
        ->name('documentos.aprobaciones')
        ->middleware('admin');
    Route::get('documentos/{documento}/preview', [\App\Http\Controllers\DocumentoController::class, 'preview'])->name('documentos.preview');
    Route::get('documentos/{documento}/download', [\App\Http\Controllers\DocumentoController::class, 'download'])->name('documentos.download');
    Route::post('documentos/{documento}/approve', [\App\Http\Controllers\DocumentoController::class, 'approve'])->name('documentos.approve');
    Route::post('documentos/{documento}/reject', [\App\Http\Controllers\DocumentoController::class, 'reject'])->name('documentos.reject');

    // Admin: Gestión de tipos de documentos
    Route::resource('tipo-documentos', \App\Http\Controllers\TipoDocumentoController::class)->middleware('can:viewAny,App\Models\TipoDocumento');
    Route::resource('admin/plantillas-documentos-trabajador', PlantillaDocumentoTrabajadorController::class)
        ->except(['show'])
        ->parameters(['plantillas-documentos-trabajador' => 'plantillaDocumentoTrabajador'])
        ->middleware('admin')
        ->names('admin.plantillas-documentos-trabajador');
    Route::resource('tipo-faenas', \App\Http\Controllers\TipoFaenaController::class)
        ->except(['show'])
        ->middleware('admin');

    // Control de Asistencia
    Route::get('asistencias', [\App\Http\Controllers\AsistenciaController::class, 'index'])->name('asistencias.index');
    Route::get('asistencias/create', [\App\Http\Controllers\AsistenciaController::class, 'create'])->name('asistencias.create');
    Route::post('asistencias', [\App\Http\Controllers\AsistenciaController::class, 'store'])->name('asistencias.store');
    Route::post('asistencias/bulk', [\App\Http\Controllers\AsistenciaController::class, 'storeBulk'])->name('asistencias.bulk');
    Route::get('asistencias/export', [\App\Http\Controllers\AsistenciaController::class, 'export'])->name('asistencias.export');

    // Herramientas
    Route::get('herramientas/cuadratura-asistencia', [CuadraturaAsistenciaController::class, 'index'])->name('herramientas.cuadratura-asistencia.index');
    Route::post('herramientas/cuadratura-asistencia', [CuadraturaAsistenciaController::class, 'extract'])->name('herramientas.cuadratura-asistencia.extract');

    // Estados de Pago
    Route::get('estados-pago', [\App\Http\Controllers\EstadoPagoController::class, 'index'])->name('estados-pago.index');
    Route::get('estados-pago/create', [\App\Http\Controllers\EstadoPagoController::class, 'create'])->name('estados-pago.create');
    Route::get('estados-pago/{estadoPago}', [\App\Http\Controllers\EstadoPagoController::class, 'show'])->name('estados-pago.show');
    Route::post('estados-pago', [\App\Http\Controllers\EstadoPagoController::class, 'store'])->name('estados-pago.store');
    Route::delete('estados-pago/{estadoPago}', [\App\Http\Controllers\EstadoPagoController::class, 'destroy'])->name('estados-pago.destroy');
    Route::patch('estados-pago/{estadoPago}/estado', [\App\Http\Controllers\EstadoPagoController::class, 'updateEstado'])->name('estados-pago.update-estado');

    // Admin: Gestión de Usuarios y Contratistas
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        // Gestión de usuarios
        Route::resource('users', \App\Http\Controllers\Admin\UserManagementController::class);

        // Gestión de contratistas
        Route::resource('contratistas', \App\Http\Controllers\Admin\ContratistaManagementController::class);
    });
});

require __DIR__.'/settings.php';
