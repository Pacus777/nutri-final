<?php

use App\Http\Controllers\RolController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\Administracion\AjusteObjetivoNutricionalController;
use App\Http\Controllers\Administracion\AlimentoController;
use App\Http\Controllers\Administracion\AsignacionNutricionistaPacienteController;
use App\Http\Controllers\Administracion\EvaluacionInicialController;
use App\Http\Controllers\Administracion\NutricionistaController;
use App\Http\Controllers\Administracion\PacienteController;
use App\Http\Controllers\Administracion\PlanificacionNutricionalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['verificar.rol:SuperAdmin,Administrador'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Usuarios
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin/usuarios')
        ->name('admin.usuarios.')
        ->group(function () {
            Route::get('/', [UsuarioController::class, 'index'])->name('index');
            Route::get('/crear', [UsuarioController::class, 'create'])->name('create');
            Route::post('/', [UsuarioController::class, 'store'])->name('store');

            Route::get('/{usuario}', [UsuarioController::class, 'show'])->name('show');
            Route::get('/{usuario}/editar', [UsuarioController::class, 'edit'])->name('edit');
            Route::get('/{usuario}/roles', [UsuarioController::class, 'edit'])->name('roles');

            Route::put('/{usuario}', [UsuarioController::class, 'update'])->name('update');

            Route::patch('/{usuario}/estado', [UsuarioController::class, 'cambiarEstado'])
                ->name('cambiar-estado');

            Route::post('/{usuario}/roles', [UsuarioController::class, 'asignarRol'])
                ->name('roles.asignar');

            Route::delete('/{usuario}/roles/{rol}', [UsuarioController::class, 'quitarRol'])
                ->name('roles.quitar');
        });

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    */
    Route::get('/admin/roles', [RolController::class, 'index'])
        ->name('admin.roles.index');

    /*
    |--------------------------------------------------------------------------
    | Nutricionistas administrativos
    |--------------------------------------------------------------------------
    */
    Route::get('/admin/nutricionistas', [NutricionistaController::class, 'index'])
        ->name('admin.nutricionistas.index');

    Route::get('/admin/nutricionistas/create', [NutricionistaController::class, 'create'])
        ->name('admin.nutricionistas.create');

    Route::post('/admin/nutricionistas', [NutricionistaController::class, 'store'])
        ->name('admin.nutricionistas.store');

    Route::get('/admin/nutricionistas/{nutricionista}', [NutricionistaController::class, 'show'])
        ->name('admin.nutricionistas.show');

    Route::get('/admin/nutricionistas/{nutricionista}/edit', [NutricionistaController::class, 'edit'])
        ->name('admin.nutricionistas.edit');

    Route::put('/admin/nutricionistas/{nutricionista}', [NutricionistaController::class, 'update'])
        ->name('admin.nutricionistas.update');

    Route::patch('/admin/nutricionistas/{nutricionista}/activar', [NutricionistaController::class, 'activar'])
        ->name('admin.nutricionistas.activar');

    Route::patch('/admin/nutricionistas/{nutricionista}/inactivar', [NutricionistaController::class, 'inactivar'])
        ->name('admin.nutricionistas.inactivar');

    /*
    |--------------------------------------------------------------------------
    | Pacientes administrativos
    |--------------------------------------------------------------------------
    */
    Route::get('/admin/pacientes', [PacienteController::class, 'index'])
        ->name('admin.pacientes.index');

    Route::get('/admin/pacientes/create', [PacienteController::class, 'create'])
        ->name('admin.pacientes.create');

    Route::post('/admin/pacientes', [PacienteController::class, 'store'])
        ->name('admin.pacientes.store');

    Route::get('/admin/pacientes/{paciente}', [PacienteController::class, 'show'])
        ->name('admin.pacientes.show');

    Route::get('/admin/pacientes/{paciente}/edit', [PacienteController::class, 'edit'])
        ->name('admin.pacientes.edit');

    Route::put('/admin/pacientes/{paciente}', [PacienteController::class, 'update'])
        ->name('admin.pacientes.update');

    Route::patch('/admin/pacientes/{paciente}/activar', [PacienteController::class, 'activar'])
        ->name('admin.pacientes.activar');

    Route::patch('/admin/pacientes/{paciente}/inactivar', [PacienteController::class, 'inactivar'])
        ->name('admin.pacientes.inactivar');

    /*
    |--------------------------------------------------------------------------
    | Asignaciones administrativas
    |--------------------------------------------------------------------------
    */
    Route::get('/admin/asignaciones', [AsignacionNutricionistaPacienteController::class, 'index'])
        ->name('admin.asignaciones.index');

    Route::get('/admin/asignaciones/create', [AsignacionNutricionistaPacienteController::class, 'create'])
        ->name('admin.asignaciones.create');

    Route::post('/admin/asignaciones', [AsignacionNutricionistaPacienteController::class, 'store'])
        ->name('admin.asignaciones.store');

    Route::get('/admin/asignaciones/{asignacion}', [AsignacionNutricionistaPacienteController::class, 'show'])
        ->name('admin.asignaciones.show');

    Route::patch('/admin/asignaciones/{asignacion}/cerrar', [AsignacionNutricionistaPacienteController::class, 'cerrar'])
        ->name('admin.asignaciones.cerrar');

    Route::post('/admin/asignaciones/reasignar', [AsignacionNutricionistaPacienteController::class, 'reasignar'])
        ->name('admin.asignaciones.reasignar');

    Route::get('/admin/pacientes/{paciente}/asignaciones', [AsignacionNutricionistaPacienteController::class, 'historialPaciente'])
        ->name('admin.pacientes.asignaciones');

    /*
    |--------------------------------------------------------------------------
    | Nutricionistas sin prefijo admin
    |--------------------------------------------------------------------------
    */
    Route::get('/nutricionistas', [NutricionistaController::class, 'index'])
        ->name('nutricionistas.index');

    Route::get('/nutricionistas/crear', [NutricionistaController::class, 'create'])
        ->name('nutricionistas.create');

    Route::post('/nutricionistas', [NutricionistaController::class, 'store'])
        ->name('nutricionistas.store');

    Route::post('/nutricionistas/registro-rapido', [NutricionistaController::class, 'guardarRegistroRapido'])
        ->name('nutricionistas.guardar-registro-rapido');

    Route::get('/nutricionistas/{nutricionista}', [NutricionistaController::class, 'show'])
        ->name('nutricionistas.show');

    Route::get('/nutricionistas/{nutricionista}/editar', [NutricionistaController::class, 'edit'])
        ->name('nutricionistas.edit');

    Route::put('/nutricionistas/{nutricionista}', [NutricionistaController::class, 'update'])
        ->name('nutricionistas.update');

    Route::patch('/nutricionistas/{nutricionista}/estado', [NutricionistaController::class, 'cambiarEstado'])
        ->name('nutricionistas.cambiar-estado');

    /*
    |--------------------------------------------------------------------------
    | Pacientes sin prefijo admin
    |--------------------------------------------------------------------------
    */
    Route::get('/pacientes', [PacienteController::class, 'index'])
        ->name('pacientes.index');

    Route::get('/pacientes/crear', [PacienteController::class, 'create'])
        ->name('pacientes.create');

    Route::post('/pacientes', [PacienteController::class, 'store'])
        ->name('pacientes.store');

    Route::post('/pacientes/registro-rapido', [PacienteController::class, 'guardarRegistroRapido'])
        ->name('pacientes.guardar-registro-rapido');

    Route::get('/pacientes/{paciente}', [PacienteController::class, 'show'])
        ->name('pacientes.show');

    Route::get('/pacientes/{paciente}/editar', [PacienteController::class, 'edit'])
        ->name('pacientes.edit');

    Route::put('/pacientes/{paciente}', [PacienteController::class, 'update'])
        ->name('pacientes.update');

    Route::patch('/pacientes/{paciente}/estado', [PacienteController::class, 'cambiarEstado'])
        ->name('pacientes.cambiar-estado');

    /*
    |--------------------------------------------------------------------------
    | Asignaciones sin prefijo admin
    |--------------------------------------------------------------------------
    */
    Route::get('/asignaciones', [AsignacionNutricionistaPacienteController::class, 'index'])
        ->name('asignaciones.index');

    Route::get('/asignaciones/crear', [AsignacionNutricionistaPacienteController::class, 'create'])
        ->name('asignaciones.create');

    Route::post('/asignaciones', [AsignacionNutricionistaPacienteController::class, 'store'])
        ->name('asignaciones.store');

    Route::get('/pacientes/{paciente}/historial-asignaciones', [AsignacionNutricionistaPacienteController::class, 'historialPaciente'])
        ->name('asignaciones.historial-paciente');

    Route::patch('/asignaciones/{asignacion}/estado', [AsignacionNutricionistaPacienteController::class, 'actualizarEstado'])
        ->name('asignaciones.actualizar-estado');

    /*
    |--------------------------------------------------------------------------
    | Evaluaciones iniciales
    |--------------------------------------------------------------------------
    */
    Route::get('/evaluaciones-iniciales/crear', [EvaluacionInicialController::class, 'create'])
        ->name('evaluaciones-iniciales.create');

    Route::post('/evaluaciones-iniciales', [EvaluacionInicialController::class, 'store'])
        ->name('evaluaciones-iniciales.store');

    Route::get('/evaluaciones-iniciales/{evaluacionInicial}', [EvaluacionInicialController::class, 'show'])
        ->name('evaluaciones-iniciales.show');

    Route::get('/evaluaciones-iniciales/{evaluacionInicial}/edit', [EvaluacionInicialController::class, 'edit'])
        ->name('evaluaciones-iniciales.edit');

    Route::put('/evaluaciones-iniciales/{evaluacionInicial}', [EvaluacionInicialController::class, 'update'])
        ->name('evaluaciones-iniciales.update');

    Route::patch('/evaluaciones-iniciales/{evaluacionInicial}/cerrar', [EvaluacionInicialController::class, 'cerrar'])
        ->name('evaluaciones-iniciales.cerrar');

    /*
    |--------------------------------------------------------------------------
    | Ajustes de objetivos
    |--------------------------------------------------------------------------
    */
    Route::get('/ajustes-objetivos-nutricionales/crear', [AjusteObjetivoNutricionalController::class, 'create'])
        ->name('ajustes-objetivos-nutricionales.create');

    Route::post('/ajustes-objetivos-nutricionales', [AjusteObjetivoNutricionalController::class, 'store'])
        ->name('ajustes-objetivos-nutricionales.store');

    Route::get('/objetivos-nutricionales/{objetivoNutricional}/ajustes', [AjusteObjetivoNutricionalController::class, 'historial'])
        ->name('ajustes-objetivos-nutricionales.historial');

    Route::get('/ajustes-objetivos-nutricionales/{ajusteObjetivoNutricional}', [AjusteObjetivoNutricionalController::class, 'show'])
        ->name('ajustes-objetivos-nutricionales.show');

    /*
    |--------------------------------------------------------------------------
    | Planificación nutricional
    |--------------------------------------------------------------------------
    */
    Route::get('/nutricion/pacientes/{paciente}/planificacion', [PlanificacionNutricionalController::class, 'show'])
        ->name('planificacion-nutricional.show');

    /*
    |--------------------------------------------------------------------------
    | Alimentos
    |--------------------------------------------------------------------------
    */
    Route::get('/alimentos/buscar', [AlimentoController::class, 'buscar'])
        ->name('alimentos.buscar');

    Route::get('/alimentos/resultados', [AlimentoController::class, 'resultados'])
        ->name('alimentos.resultados');

    Route::post('/alimentos/importar', [AlimentoController::class, 'importar'])
        ->name('alimentos.importar');

    Route::get('/alimentos/{alimento}', [AlimentoController::class, 'show'])
        ->name('alimentos.show');
});