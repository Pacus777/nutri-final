<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

/*
|--------------------------------------------------------------------------
| Rutas autenticadas sin rol activo obligatorio
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'check.estado'])->group(function () {
    require __DIR__ . '/web/roles.php';
});

/*
|--------------------------------------------------------------------------
| Rutas autenticadas con rol activo obligatorio
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'check.estado', 'verificar.rol.activo'])->group(function () {
    require __DIR__ . '/web/dashboard.php';
    require __DIR__ . '/web/profile.php';
    require __DIR__ . '/web/paciente.php';
    require __DIR__ . '/web/nutricionista.php';
    require __DIR__ . '/web/shared.php';
    require __DIR__ . '/web/reportes.php';
    require __DIR__ . '/web/admin.php';

    // Rutas de Notificaciones API
    Route::get('/api/notificaciones/no-leidas', [\App\Http\Controllers\NotificacionController::class, 'obtenerNoLeidas'])
        ->name('api.notificaciones.no-leidas');
    Route::get('/api/notificaciones/todas', [\App\Http\Controllers\NotificacionController::class, 'obtenerTodas'])
        ->name('api.notificaciones.todas');
    Route::post('/api/notificaciones/{notificacion}/leer', [\App\Http\Controllers\NotificacionController::class, 'marcarLeida'])
        ->name('api.notificaciones.leer');
    Route::post('/api/notificaciones/{notificacion}/alternar', [\App\Http\Controllers\NotificacionController::class, 'alternarEstado'])
        ->name('api.notificaciones.alternar');
    Route::post('/api/notificaciones/leer-todas', [\App\Http\Controllers\NotificacionController::class, 'marcarTodasLeidas'])
        ->name('api.notificaciones.leer-todas');
    Route::get('/api/paciente/recomendaciones', [\App\Http\Controllers\NotificacionController::class, 'obtenerRecomendaciones'])
        ->name('api.paciente.recomendaciones');
});

require __DIR__ . '/auth.php';