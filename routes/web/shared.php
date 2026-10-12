<?php

use App\Http\Controllers\Administracion\ContextoPacienteController;
use App\Http\Controllers\Administracion\ObjetivoNutricionalController;
use App\Http\Controllers\Administracion\PlanNutricionalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['verificar.rol:SuperAdmin,Administrador,Nutricionista'])->group(function () {
    Route::get('/contextos-paciente', [ContextoPacienteController::class, 'index'])
        ->name('contextos-paciente.index');

    Route::get('/contextos-paciente/crear', [ContextoPacienteController::class, 'create'])
        ->name('contextos-paciente.create');

    Route::post('/contextos-paciente', [ContextoPacienteController::class, 'store'])
        ->name('contextos-paciente.store');

    Route::get('/pacientes/{paciente}/contextos', [ContextoPacienteController::class, 'historial'])
        ->name('contextos-paciente.historial');

    Route::get('/contextos-paciente/{contextoPaciente}/edit', [ContextoPacienteController::class, 'edit'])
        ->name('contextos-paciente.edit');

    Route::put('/contextos-paciente/{contextoPaciente}', [ContextoPacienteController::class, 'update'])
        ->name('contextos-paciente.update');

    Route::patch('/contextos-paciente/{contextoPaciente}/inactivar', [ContextoPacienteController::class, 'inactivar'])
        ->name('contextos-paciente.inactivar');

    Route::get('/objetivos-nutricionales/crear', [ObjetivoNutricionalController::class, 'create'])
        ->middleware('verificar.rol:Administrador,SuperAdmin,Nutricionista')
        ->name('objetivos-nutricionales.create');

    Route::post('/objetivos-nutricionales', [ObjetivoNutricionalController::class, 'store'])
        ->middleware('verificar.rol:Administrador,SuperAdmin,Nutricionista')
        ->name('objetivos-nutricionales.store');

    Route::get('/planes-nutricionales/crear', [PlanNutricionalController::class, 'create'])
        ->middleware('verificar.rol:Administrador,SuperAdmin,Nutricionista')
        ->name('planes-nutricionales.create');

    Route::post('/planes-nutricionales', [PlanNutricionalController::class, 'store'])
        ->middleware('verificar.rol:Administrador,SuperAdmin,Nutricionista')
        ->name('planes-nutricionales.store');

    Route::get('/pacientes/{paciente}/plan-nutricional-vigente', [PlanNutricionalController::class, 'vigente'])
        ->name('planes-nutricionales.vigente');

    Route::get('/planes-nutricionales/{planNutricional}', [PlanNutricionalController::class, 'show'])
        ->name('planes-nutricionales.show');

    Route::get('/planes-nutricionales/{planNutricional}/edit', [PlanNutricionalController::class, 'edit'])
        ->name('planes-nutricionales.edit');

    Route::put('/planes-nutricionales/{planNutricional}', [PlanNutricionalController::class, 'update'])
        ->name('planes-nutricionales.update');

    Route::patch('/planes-nutricionales/{planNutricional}/cerrar', [PlanNutricionalController::class, 'cerrar'])
        ->name('planes-nutricionales.cerrar');

    Route::patch('/planes-nutricionales/{planNutricional}/reemplazar', [PlanNutricionalController::class, 'reemplazar'])
        ->name('planes-nutricionales.reemplazar');

    Route::get('/pacientes/{paciente}/planes', [PlanNutricionalController::class, 'porPaciente'])
        ->name('planes-nutricionales.paciente');

    Route::get('/buscar-global', [\App\Http\Controllers\BuscarGlobalController::class, 'buscar'])
        ->name('buscar-global');
});