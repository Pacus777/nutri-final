<?php

use App\Http\Controllers\Reportes\ReporteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['verificar.rol:SuperAdmin,Administrador'])->group(function () {
    Route::get('/reportes', [ReporteController::class, 'index'])
        ->name('reportes.index');

    Route::get('/reportes/general/pdf', [ReporteController::class, 'generalPdf'])
        ->name('reportes.general.pdf');

    Route::get('/reportes/nutricionista/pdf', [ReporteController::class, 'nutricionistaPdf'])
        ->name('reportes.nutricionista.pdf');

    Route::get('/reportes/alimentacion/pdf', [ReporteController::class, 'alimentacionPdf'])
        ->name('reportes.alimentacion.pdf');

    Route::get('/reportes/paciente/pdf', [ReporteController::class, 'pacientePdf'])
        ->name('reportes.paciente.pdf');
});