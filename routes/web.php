<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RolActivoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\Administracion\NutricionistaController;

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
| Rutas autenticadas que NO exigen rol activo
|--------------------------------------------------------------------------
| Aquí van las rutas que sirven justamente para establecer el rol activo.
*/
Route::middleware(['auth', 'check.estado'])->group(function () {
    Route::get('/seleccionar-rol', [RolActivoController::class, 'seleccionar'])
        ->name('roles.seleccionar');

    Route::post('/seleccionar-rol', [RolActivoController::class, 'guardar'])
        ->name('roles.guardar');

    Route::post('/cambiar-rol-activo', [RolActivoController::class, 'cambiar'])
        ->name('roles.cambiar');
});

/*
|--------------------------------------------------------------------------
| Rutas autenticadas que SÍ exigen rol activo
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'check.estado', 'verificar.rol.activo'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(['verificar.rol:SuperAdmin'])->group(function () {
        Route::get('/panel-superadmin', function () {
            return 'Bienvenido, SuperAdmin';
        })->name('panel.superadmin');
    });

    Route::middleware(['verificar.rol:SuperAdmin,Administrador'])->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/crear', [UsuarioController::class, 'create'])->name('usuarios.create');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::get('/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
        Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::patch('/usuarios/{usuario}/estado', [UsuarioController::class, 'cambiarEstado'])
            ->name('usuarios.cambiar-estado');

            Route::get('/nutricionistas', [NutricionistaController::class, 'index'])->name('nutricionistas.index');
            Route::get('/nutricionistas/crear', [NutricionistaController::class, 'create'])->name('nutricionistas.create');
            Route::post('/nutricionistas', [NutricionistaController::class, 'store'])->name('nutricionistas.store');
            Route::get('/nutricionistas/{nutricionista}', [NutricionistaController::class, 'show'])->name('nutricionistas.show');
            Route::get('/nutricionistas/{nutricionista}/editar', [NutricionistaController::class, 'edit'])->name('nutricionistas.edit');
            Route::put('/nutricionistas/{nutricionista}', [NutricionistaController::class, 'update'])->name('nutricionistas.update');
            Route::patch('/nutricionistas/{nutricionista}/estado', [NutricionistaController::class, 'cambiarEstado'])->name('nutricionistas.cambiar-estado');
        });



});

require __DIR__ . '/auth.php';