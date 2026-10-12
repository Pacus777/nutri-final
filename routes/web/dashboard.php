<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Administracion\SuperAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

Route::get('/dashboard/admin', [DashboardController::class, 'index'])
    ->name('dashboard.admin');

Route::get('/dashboard/nutricionista', [DashboardController::class, 'index'])
    ->name('dashboard.nutricionista');

Route::middleware(['verificar.rol:SuperAdmin'])->group(function () {
    Route::get('/panel-superadmin', [SuperAdminController::class, 'index'])->name('panel.superadmin');
    Route::get('/panel-superadmin/documentacion', [SuperAdminController::class, 'documentacion'])->name('panel.superadmin.documentacion');
    Route::post('/panel-superadmin/backups', [SuperAdminController::class, 'crearBackup'])->name('panel.superadmin.backups.crear');
    Route::get('/panel-superadmin/backups/{filename}/descargar', [SuperAdminController::class, 'descargarBackup'])->name('panel.superadmin.backups.descargar');
    Route::post('/panel-superadmin/backups/{filename}/restaurar', [SuperAdminController::class, 'restaurarBackup'])->name('panel.superadmin.backups.restaurar');
    Route::delete('/panel-superadmin/backups/{filename}', [SuperAdminController::class, 'eliminarBackup'])->name('panel.superadmin.backups.eliminar');
    
    Route::get('/panel-superadmin/logs/descargar', [SuperAdminController::class, 'descargarLogs'])->name('panel.superadmin.logs.descargar');
});