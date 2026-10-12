<?php

use App\Http\Controllers\RolActivoController;
use Illuminate\Support\Facades\Route;

Route::get('/seleccionar-rol', [RolActivoController::class, 'seleccionar'])
    ->name('roles.seleccionar');

Route::post('/seleccionar-rol', [RolActivoController::class, 'guardar'])
    ->name('roles.guardar');

Route::post('/cambiar-rol-activo', [RolActivoController::class, 'cambiar'])
    ->name('roles.cambiar');