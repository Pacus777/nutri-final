<?php

use App\Http\Controllers\Administracion\PacienteController;
use App\Http\Controllers\Paciente\DashboardPacienteController;
use App\Http\Controllers\Paciente\RegistroAlimenticioController;
use App\Http\Controllers\Administracion\EvaluacionInicialController;

use Illuminate\Support\Facades\Route;

Route::middleware([
                    'auth',
                    'verificar.rol.activo',
                    'verificar.rol:Paciente',
                ])->group(function () {
    Route::get('/dashboard/paciente', [DashboardPacienteController::class, 'index'])
        ->name('dashboard.paciente');

    Route::post('/pacientes/{paciente}/objetivos/{objetivo}/progreso', [DashboardPacienteController::class, 'actualizarProgresoObjetivo'])
        ->name('paciente.objetivos.progreso');

    Route::get('/paciente/ficha', [PacienteController::class, 'fichaPropia'])
        ->name('paciente.ficha');

    Route::get('/paciente/evaluacion-inicial', [EvaluacionInicialController::class, 'onboardingPaciente'])
        ->name('paciente.evaluacion-inicial.onboarding');

    Route::post('/paciente/evaluacion-inicial', [EvaluacionInicialController::class, 'guardarOnboardingPaciente'])
        ->name('paciente.evaluacion-inicial.guardar');

    Route::patch('/paciente/evaluacion-inicial/{evaluacionInicial}/enviar-revision', [EvaluacionInicialController::class, 'enviarRevisionPaciente'])
        ->name('paciente.evaluacion-inicial.enviar-revision');

    Route::get('/pacientes/{paciente}/registros-alimenticios', [RegistroAlimenticioController::class, 'historial'])
        ->name('paciente.registros-alimenticios.historial');

    Route::get('/registros-alimenticios/{registroAlimenticio}', [RegistroAlimenticioController::class, 'show'])
        ->name('paciente.registros-alimenticios.show');

    Route::get('/pacientes/{paciente}/registros-alimenticios/crear', [RegistroAlimenticioController::class, 'create'])
        ->name('paciente.registros-alimenticios.create');

    Route::get('/pacientes/registros-alimenticios/buscar-alimentos', [RegistroAlimenticioController::class, 'buscarAlimentos'])
        ->name('paciente.registros-alimenticios.buscar-alimentos');

    Route::post('/pacientes/registros-alimenticios/agregar-alimento-temporal', [RegistroAlimenticioController::class, 'agregarAlimentoTemporal'])
        ->name('paciente.registros-alimenticios.agregar-alimento-temporal');

    Route::post('/pacientes/registros-alimenticios', [RegistroAlimenticioController::class, 'store'])
        ->name('paciente.registros-alimenticios.store');

    Route::post('/pacientes/{paciente}/registros-alimenticios/copiar-anterior', [RegistroAlimenticioController::class, 'copiarRegistroAnterior'])
        ->name('paciente.registros-alimenticios.copiar-anterior');

    Route::post('/registros-alimenticios/{registroAlimenticio}/calcular-composicion', [RegistroAlimenticioController::class, 'calcularComposicion'])
        ->name('paciente.registros-alimenticios.calcular-composicion');

    Route::get('/registros-alimenticios/{registroAlimenticio}/consolidado', [RegistroAlimenticioController::class, 'consolidado'])
        ->name('paciente.registros-alimenticios.consolidado');

    Route::get('/pacientes/{paciente}/registros-alimenticios/calculos/historial', [RegistroAlimenticioController::class, 'historialCalculos'])
        ->name('paciente.registros-alimenticios.historial-calculos');

    Route::get('/paciente/evaluacion-inicial', [EvaluacionInicialController::class, 'onboardingPaciente'])
        ->name('paciente.evaluacion-inicial.onboarding');

    Route::post('/paciente/evaluacion-inicial', [EvaluacionInicialController::class, 'guardarOnboardingPaciente'])
        ->name('paciente.evaluacion-inicial.guardar');
});