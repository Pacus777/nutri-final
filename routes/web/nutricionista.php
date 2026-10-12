<?php

use App\Http\Controllers\Administracion\AsignacionNutricionistaPacienteController;
use App\Http\Controllers\Administracion\NutricionistaController;
use App\Http\Controllers\Administracion\EvaluacionInicialController;
use App\Http\Controllers\Administracion\ObjetivoNutricionalController;
use App\Http\Controllers\Nutricionista\SeguimientoNutricionalController;
use App\Http\Controllers\Nutricionista\ValidacionRegistroAlimenticioController;
use App\Http\Controllers\Administracion\ContextoPacienteController;
use App\Http\Controllers\Administracion\AjusteObjetivoNutricionalController;


use Illuminate\Support\Facades\Route;

Route::middleware([
                        'auth',
                        'verificar.rol.activo',
                        'verificar.rol:Nutricionista',
                    ])->group(function () {


    
    Route::get('/nutricionista/validaciones-informacion/pendiente', function () {
        return redirect()->route('nutricionista.validaciones-informacion.pendientes');
    })->name('nutricionista.validaciones-informacion.pendiente');

    Route::get('/nutricionista/validaciones-informacion/pendientes', [ValidacionRegistroAlimenticioController::class, 'pendientes'])
        ->name('nutricionista.validaciones-informacion.pendientes');

    Route::get('/nutricionista/registros-alimenticios/{registroAlimenticio}/validar', [ValidacionRegistroAlimenticioController::class, 'validar'])
        ->name('nutricionista.validaciones-informacion.validar');

    Route::post('/nutricionista/registros-alimenticios/{registroAlimenticio}/validar', [ValidacionRegistroAlimenticioController::class, 'guardar'])
        ->name('nutricionista.validaciones-informacion.guardar');

    Route::get('/nutricionista/pacientes/{paciente}/validaciones/historial', [ValidacionRegistroAlimenticioController::class, 'historialPaciente'])
        ->name('nutricionista.validaciones-informacion.historial-paciente');

    Route::get('/nutricionista/pacientes/{paciente}/validaciones/observaciones', [ValidacionRegistroAlimenticioController::class, 'observacionesPaciente'])
        ->name('nutricionista.validaciones-informacion.observaciones-paciente');

    Route::post('/nutricionista/seguimientos', [SeguimientoNutricionalController::class, 'store'])
        ->name('nutricionista.seguimientos.store');

    Route::get('/nutricionista/pacientes/{paciente}/seguimientos/crear', [SeguimientoNutricionalController::class, 'create'])
        ->name('nutricionista.seguimientos.create');

    Route::get('/nutricionista/pacientes/{paciente}/seguimientos/historial', [SeguimientoNutricionalController::class, 'historial'])
        ->name('nutricionista.seguimientos.historial');

    Route::get('/nutricionista/pacientes/{paciente}/seguimientos/comparativo', [SeguimientoNutricionalController::class, 'comparativo'])
        ->name('nutricionista.seguimientos.comparativo');

    Route::get('/nutricionista/pacientes/{paciente}/seguimientos/consolidado', [SeguimientoNutricionalController::class, 'consolidado'])
        ->name('nutricionista.seguimientos.consolidado');

    Route::get('/nutricionista/pacientes', [AsignacionNutricionistaPacienteController::class, 'pacientesAsignados'])
        ->name('nutricionista.pacientes.index');

    Route::get('/nutricionista/pacientes/{paciente}/evaluacion-inicial', [EvaluacionInicialController::class, 'showPaciente'])
        ->name('nutricionista.evaluaciones-iniciales.show-paciente');

    Route::get('/nutricionista/pacientes/{paciente}/evaluacion-inicial/crear', [EvaluacionInicialController::class, 'createPaciente'])
        ->name('nutricionista.evaluaciones-iniciales.create-paciente');

    Route::post('/nutricionista/pacientes/{paciente}/evaluacion-inicial', [EvaluacionInicialController::class, 'storePaciente'])
        ->name('nutricionista.evaluaciones-iniciales.store-paciente');

    Route::get('/nutricionista/evaluaciones-iniciales/{evaluacionInicial}/editar', [EvaluacionInicialController::class, 'editNutricionista'])
        ->name('nutricionista.evaluaciones-iniciales.edit');

    Route::put('/nutricionista/evaluaciones-iniciales/{evaluacionInicial}', [EvaluacionInicialController::class, 'updateNutricionista'])
        ->name('nutricionista.evaluaciones-iniciales.update');

    Route::patch('/nutricionista/evaluaciones-iniciales/{evaluacionInicial}/aprobar', [EvaluacionInicialController::class, 'aprobarNutricionista'])
        ->name('nutricionista.evaluaciones-iniciales.aprobar');

    Route::patch('/nutricionista/evaluaciones-iniciales/{evaluacionInicial}/cerrar', [EvaluacionInicialController::class, 'cerrarNutricionista'])
        ->name('nutricionista.evaluaciones-iniciales.cerrar');

    // Objetivos nutricionales - flujo clínico del nutricionista
    Route::get('/nutricionista/pacientes/{paciente}/objetivos', [ObjetivoNutricionalController::class, 'porPacienteNutricionista'])
        ->name('nutricionista.objetivos-nutricionales.paciente');

    Route::get('/nutricionista/pacientes/{paciente}/objetivos/crear', [ObjetivoNutricionalController::class, 'createPaciente'])
        ->name('nutricionista.objetivos-nutricionales.create-paciente');

    Route::post('/nutricionista/pacientes/{paciente}/objetivos', [ObjetivoNutricionalController::class, 'storePaciente'])
        ->name('nutricionista.objetivos-nutricionales.store-paciente');

    Route::get('/nutricionista/objetivos-nutricionales/{objetivoNutricional}', [ObjetivoNutricionalController::class, 'showNutricionista'])
        ->name('nutricionista.objetivos-nutricionales.show');

    Route::get('/nutricionista/objetivos-nutricionales/{objetivoNutricional}/editar', [ObjetivoNutricionalController::class, 'editNutricionista'])
        ->name('nutricionista.objetivos-nutricionales.edit');

    Route::put('/nutricionista/objetivos-nutricionales/{objetivoNutricional}', [ObjetivoNutricionalController::class, 'updateNutricionista'])
        ->name('nutricionista.objetivos-nutricionales.update');

    Route::patch('/nutricionista/objetivos-nutricionales/{objetivoNutricional}/cumplido', [ObjetivoNutricionalController::class, 'marcarCumplidoNutricionista'])
        ->name('nutricionista.objetivos-nutricionales.cumplido');

    Route::patch('/nutricionista/objetivos-nutricionales/{objetivoNutricional}/pausar', [ObjetivoNutricionalController::class, 'pausarNutricionista'])
        ->name('nutricionista.objetivos-nutricionales.pausar');

    Route::patch('/nutricionista/objetivos-nutricionales/{objetivoNutricional}/reactivar', [ObjetivoNutricionalController::class, 'reactivarNutricionista'])
        ->name('nutricionista.objetivos-nutricionales.reactivar');

    Route::patch('/nutricionista/objetivos-nutricionales/{objetivoNutricional}/cerrar', [ObjetivoNutricionalController::class, 'cerrarNutricionista'])
        ->name('nutricionista.objetivos-nutricionales.cerrar');

    Route::patch('/nutricionista/objetivos-nutricionales/{objetivoNutricional}/cancelar', [ObjetivoNutricionalController::class, 'cancelarNutricionista'])
        ->name('nutricionista.objetivos-nutricionales.cancelar');

    Route::get('/nutricionista/pacientes/{paciente}', [NutricionistaController::class, 'showPacienteAsignado'])
        ->name('nutricionista.pacientes.show');

    Route::post('/nutricionista/pacientes/{paciente}/objetivos/{objetivo}/asignar', [NutricionistaController::class, 'asignarObjetivoPaciente'])
        ->name('nutricionista.pacientes.objetivos.asignar');

    Route::get('/pacientes/{paciente}/objetivos-nutricionales', [ObjetivoNutricionalController::class, 'porPaciente'])
        ->name('objetivos-nutricionales.paciente');

    Route::patch('/objetivos-nutricionales/{objetivoNutricional}/cerrar', [ObjetivoNutricionalController::class, 'cerrar'])
        ->name('objetivos-nutricionales.cerrar');

    Route::get('/objetivos-nutricionales/{objetivoNutricional}', [ObjetivoNutricionalController::class, 'show'])
        ->name('objetivos-nutricionales.show');

    Route::get('/objetivos-nutricionales/{objetivoNutricional}/edit', [ObjetivoNutricionalController::class, 'edit'])
        ->name('objetivos-nutricionales.edit');

    Route::put('/objetivos-nutricionales/{objetivoNutricional}', [ObjetivoNutricionalController::class, 'update'])
        ->name('objetivos-nutricionales.update');

    Route::patch('/objetivos-nutricionales/{objetivoNutricional}/cumplido', [ObjetivoNutricionalController::class, 'marcarCumplido'])
        ->name('objetivos-nutricionales.cumplido');

    Route::patch('/objetivos-nutricionales/{objetivoNutricional}/pausar', [ObjetivoNutricionalController::class, 'pausar'])
        ->name('objetivos-nutricionales.pausar');

        // Ajustes de objetivos nutricionales - flujo clínico del nutricionista
    Route::get('/nutricionista/objetivos-nutricionales/{objetivoNutricional}/ajustes', [AjusteObjetivoNutricionalController::class, 'historialNutricionista'])
        ->name('nutricionista.ajustes-objetivos-nutricionales.historial');

    Route::get('/nutricionista/objetivos-nutricionales/{objetivoNutricional}/ajustes/crear', [AjusteObjetivoNutricionalController::class, 'createNutricionista'])
        ->name('nutricionista.ajustes-objetivos-nutricionales.create');

    Route::post('/nutricionista/objetivos-nutricionales/{objetivoNutricional}/ajustes', [AjusteObjetivoNutricionalController::class, 'storeNutricionista'])
        ->name('nutricionista.ajustes-objetivos-nutricionales.store');

    Route::get('/nutricionista/ajustes-objetivos-nutricionales/{ajusteObjetivoNutricional}', [AjusteObjetivoNutricionalController::class, 'showNutricionista'])
        ->name('nutricionista.ajustes-objetivos-nutricionales.show');

    Route::get('/nutricionista/pacientes/{paciente}/evaluacion-inicial', [EvaluacionInicialController::class, 'showPaciente'])
        ->name('nutricionista.evaluaciones-iniciales.show-paciente');

    Route::get('/nutricionista/pacientes/{paciente}/evaluacion-inicial/crear', [EvaluacionInicialController::class, 'createPaciente'])
        ->name('nutricionista.evaluaciones-iniciales.create-paciente');

    Route::post('/nutricionista/pacientes/{paciente}/evaluacion-inicial', [EvaluacionInicialController::class, 'storePaciente'])
        ->name('nutricionista.evaluaciones-iniciales.store-paciente');

    Route::get('/nutricionista/evaluaciones-iniciales/{evaluacionInicial}/editar', [EvaluacionInicialController::class, 'editNutricionista'])
        ->name('nutricionista.evaluaciones-iniciales.edit');

    Route::put('/nutricionista/evaluaciones-iniciales/{evaluacionInicial}', [EvaluacionInicialController::class, 'updateNutricionista'])
        ->name('nutricionista.evaluaciones-iniciales.update');

    Route::patch('/nutricionista/evaluaciones-iniciales/{evaluacionInicial}/cerrar', [EvaluacionInicialController::class, 'cerrarNutricionista'])
        ->name('nutricionista.evaluaciones-iniciales.cerrar');

    Route::get('/nutricionista/pacientes/{paciente}/contextos', [ContextoPacienteController::class, 'historialNutricionista'])
        ->name('nutricionista.contextos-paciente.historial');

    Route::get('/nutricionista/pacientes/{paciente}/contextos/crear', [ContextoPacienteController::class, 'createPaciente'])
        ->name('nutricionista.contextos-paciente.create-paciente');

    Route::post('/nutricionista/pacientes/{paciente}/contextos', [ContextoPacienteController::class, 'storePaciente'])
        ->name('nutricionista.contextos-paciente.store-paciente');

    Route::get('/nutricionista/contextos-paciente/{contextoPaciente}/editar', [ContextoPacienteController::class, 'editNutricionista'])
        ->name('nutricionista.contextos-paciente.edit');

    Route::put('/nutricionista/contextos-paciente/{contextoPaciente}', [ContextoPacienteController::class, 'updateNutricionista'])
        ->name('nutricionista.contextos-paciente.update');

    Route::patch('/nutricionista/contextos-paciente/{contextoPaciente}/inactivar', [ContextoPacienteController::class, 'inactivarNutricionista'])
        ->name('nutricionista.contextos-paciente.inactivar');

    // Validación profesional de clusters e inferencia K-Means (Fase 2C.2)
    Route::post('/nutricionista/ml/clusters/{cluster}/validar', [\App\Http\Controllers\Nutricionista\MlClusterValidationController::class, 'validarCluster'])
        ->name('nutricionista.ml.clusters.validar');

    Route::post('/nutricionista/ml/predicciones/{prediccion}/validar', [\App\Http\Controllers\Nutricionista\MlClusterValidationController::class, 'validarPrediccion'])
        ->name('nutricionista.ml.predicciones.validar');

    // Captura independiente de etiquetas profesionales de riesgo (Fase 2D.1)
    Route::get('/nutricionista/ml/etiquetado-riesgo', [\App\Http\Controllers\Nutricionista\EtiquetadoRiesgoController::class, 'index'])
        ->name('nutricionista.etiquetado-riesgo.index');

    Route::get('/nutricionista/ml/etiquetado-riesgo/{featureNutricional}', [\App\Http\Controllers\Nutricionista\EtiquetadoRiesgoController::class, 'show'])
        ->name('nutricionista.etiquetado-riesgo.show');

    Route::post('/nutricionista/ml/etiquetado-riesgo/{featureNutricional}', [\App\Http\Controllers\Nutricionista\EtiquetadoRiesgoController::class, 'store'])
        ->name('nutricionista.etiquetado-riesgo.store');

    // Motor Inteligente Nutricional y Recomendaciones Asistidas (Fase 2G)
    Route::post('/nutricionista/recomendaciones/{recomendacion}/aprobar', [\App\Http\Controllers\Nutricionista\RecomendacionNutricionalController::class, 'aprobar'])
        ->name('nutricionista.recomendaciones.aprobar');

    Route::post('/nutricionista/recomendaciones/{recomendacion}/descartar', [\App\Http\Controllers\Nutricionista\RecomendacionNutricionalController::class, 'descartar'])
        ->name('nutricionista.recomendaciones.descartar');

    Route::post('/nutricionista/pacientes/{paciente}/recomendaciones', [\App\Http\Controllers\Nutricionista\RecomendacionNutricionalController::class, 'storeManual'])
        ->name('nutricionista.recomendaciones.store-manual');

    Route::post('/nutricionista/pacientes/{paciente}/motor-analisis/reanalizar', [\App\Http\Controllers\Nutricionista\RecomendacionNutricionalController::class, 'reanalizarMotor'])
        ->name('nutricionista.motor-analisis.reanalizar');
    
});