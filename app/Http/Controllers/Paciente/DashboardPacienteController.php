<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Models\ObjetivoNutricional;
use App\Models\Paciente;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardPacienteController extends Controller
{
    public function index(): Response
    {
        $usuario = Auth::user();

        $paciente = Paciente::with('usuario')
            ->where('cod_usuarios_pac', $usuario->cod_usuarios)
            ->firstOrFail();

        $recomendaciones = $paciente->seguimientosNutricionales()
            ->with('nutricionista.usuario')
            ->orderByDesc('fec_seg')
            ->orderByDesc('cod_seguimientos_nutricionales')
            ->limit(5)
            ->get()
            ->map(fn ($s) => [
                'cod_seguimientos_nutricionales' => $s->cod_seguimientos_nutricionales,
                'fecha' => optional($s->fec_seg)->format('Y-m-d'),
                'nutricionista' => $s->nutricionista?->usuario?->nombre_completo ?? 'Nutricionista',
                'recomendaciones' => $s->res_seg,
                'cambios_sugeridos' => $s->cam_seg,
            ]);

        // --- CÁLCULO DE RESUMEN SEMANAL MOTIVACIONAL ---
        $fechaInicio = Carbon::now()->subDays(6)->toDateString();
        $fechaFin = Carbon::now()->toDateString();

        $registros = $paciente->registrosAlimenticios()
            ->whereBetween('fec_ral', [$fechaInicio, $fechaFin])
            ->with('detalles.alimento.composicionVigente')
            ->get();

        $diasStats = [];
        $diasRegistrados = 0;

        for ($i = 6; $i >= 0; $i--) {
            $carbonFecha = Carbon::now()->subDays($i);
            $fechaStr = $carbonFecha->toDateString();
            
            $nombreDia = match ($carbonFecha->dayOfWeek) {
                Carbon::MONDAY => 'Lun',
                Carbon::TUESDAY => 'Mar',
                Carbon::WEDNESDAY => 'Mié',
                Carbon::THURSDAY => 'Jue',
                Carbon::FRIDAY => 'Vie',
                Carbon::SATURDAY => 'Sáb',
                Carbon::SUNDAY => 'Dom',
            };

            $registro = $registros->first(fn($r) => optional($r->fec_ral)->format('Y-m-d') === $fechaStr);
            $caloriasConsumidas = 0;
            $tieneDetalles = false;

            if ($registro && $registro->detalles->isNotEmpty()) {
                $tieneDetalles = true;
                $diasRegistrados++;

                foreach ($registro->detalles as $detalle) {
                    $composicion = $detalle->alimento?->composicionVigente;
                    if ($composicion) {
                        $cantidad = max((float) $detalle->can_dra, 0);
                        $unidad = (string) $detalle->uni_dra;
                        $factor = 0.0;
                        if ($cantidad > 0) {
                            if (in_array($unidad, ['gramos', 'mililitros'], true)) {
                                $factor = $cantidad / 100;
                            } else {
                                $factor = 1.0;
                            }
                        }
                        $caloriasConsumidas += (float) $composicion->cal_tcn * $factor;
                    }
                }
            }

            $diasStats[] = [
                'fecha' => $fechaStr,
                'nombre_dia' => $nombreDia,
                'completo' => $tieneDetalles,
                'calorias' => round($caloriasConsumidas, 1),
            ];
        }

        // Calcular racha de días consecutivos
        $racha = 0;
        $fechaEvaluar = Carbon::now()->startOfDay();

        $tieneHoy = $paciente->registrosAlimenticios()
            ->whereDate('fec_ral', $fechaEvaluar->toDateString())
            ->whereHas('detalles')
            ->exists();

        $tieneAyer = $paciente->registrosAlimenticios()
            ->whereDate('fec_ral', $fechaEvaluar->copy()->subDay()->toDateString())
            ->whereHas('detalles')
            ->exists();

        if ($tieneHoy || $tieneAyer) {
            $rachaEvaluar = $tieneHoy ? $fechaEvaluar : $fechaEvaluar->copy()->subDay();
            while (true) {
                $tieneReg = $paciente->registrosAlimenticios()
                    ->whereDate('fec_ral', $rachaEvaluar->toDateString())
                    ->whereHas('detalles')
                    ->exists();
                if ($tieneReg) {
                    $racha++;
                    $rachaEvaluar->subDay();
                } else {
                    break;
                }
            }
        }

        $metas = $this->calcularMetasNutricionales($paciente);
        $adherencia = $paciente->calcularEstadoAdherencia();

        $diasConDatos = array_filter($diasStats, fn($d) => $d['completo']);
        $promedioCalorias = 0;
        if (!empty($diasConDatos)) {
            $sumCal = array_sum(array_column($diasConDatos, 'calorias'));
            $promedioCalorias = round($sumCal / count($diasConDatos), 1);
        }

        // Mensaje motivacional clínico empático sin emojis
        $mensaje = "Esta semana es una nueva oportunidad para retomar el registro de tus comidas y alcanzar tus metas.";
        if ($racha >= 5) {
            $mensaje = "Rendimiento destacado. Tienes una racha espectacular de {$racha} días registrando. Tu constancia te llevará muy lejos.";
        } elseif ($racha >= 3) {
            $mensaje = "Excelente ritmo. Tienes una racha de {$racha} días seguidos. Sigue así para afianzar tus nuevos hábitos.";
        } elseif ($adherencia['estado'] === 'verde') {
            $mensaje = "Comportamiento óptimo. Tu adherencia nutricional es de nivel excelente. Mantén este gran compromiso con tu salud.";
        } elseif ($diasRegistrados >= 4) {
            $mensaje = "Vas por excelente camino. Has registrado {$diasRegistrados} de los últimos 7 días. ¡Mañana sumamos uno más!";
        } elseif ($diasRegistrados > 0) {
            $mensaje = "Buen esfuerzo. Cada registro cuenta para construir hábitos duraderos. ¡Vamos a por más constancia esta semana!";
        }

        $resumenSemanal = [
            'dias_stats' => $diasStats,
            'dias_registrados' => $diasRegistrados,
            'racha_actual' => $racha,
            'promedio_calorias' => $promedioCalorias,
            'meta_calorias' => $metas['calorias'],
            'adherencia' => $adherencia,
            'mensaje_motivacional' => $mensaje,
        ];

        $objetivosActivos = $paciente->objetivosNutricionales()
            ->whereIn('est_obe', [
                \App\Models\ObjetivoNutricional::ESTADO_ACTIVO,
                \App\Models\ObjetivoNutricional::ESTADO_PAUSADO,
            ])
            ->latest('fec_obe')
            ->latest('cod_objetivos_nutricionales')
            ->get()
            ->map(fn ($o) => [
                'cod_objetivos_nutricionales' => $o->cod_objetivos_nutricionales,
                'des_obe' => $o->des_obe,
                'est_obe' => $o->est_obe,
                'fec_obe' => optional($o->fec_obe)->format('Y-m-d'),
                'progreso_actual' => (int) ($o->progreso_actual ?? 0),
                'meta_progreso' => (int) ($o->meta_progreso ?: 7),
                'unidad_progreso' => $o->unidad_progreso ?: 'veces',
                'porcentaje' => $o->calcularPorcentajeProgreso(),
            ]);

        return Inertia::render('Dashboard/Paciente', [
            'paciente' => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'historial_peso' => $paciente->getHistorialPeso(),
                'usuario' => [
                    'nombre_completo' => $paciente->usuario->nombre_completo,
                    'cor_usu' => $paciente->usuario->cor_usu,
                ],
            ],
            'recomendaciones' => $recomendaciones,
            'resumenSemanal' => $resumenSemanal,
            'objetivosActivos' => $objetivosActivos,
        ]);
    }

    private function calcularMetasNutricionales(Paciente $paciente): array
    {
        $evaluacion = $paciente->evaluacionesIniciales()->where('est_evi', 'activa')->first();
        
        $peso = $evaluacion?->peso_evi ?? $paciente->pes_pac ?? 70;
        $estatura = $evaluacion?->estatura_evi ?? $paciente->estatura_pac ?? 1.70;
        $sexoVal = $paciente->sex_pac;
        $sexoString = $sexoVal instanceof \App\Enums\SexoPaciente ? $sexoVal->value : ($sexoVal ?? 'm');
        $sexo = strtolower($sexoString);
        $edad = $paciente->edad ?? 30;

        if ($estatura > 3) {
            $estatura = $estatura / 100;
        }
        $estaturaCm = $estatura * 100;

        if (str_starts_with($sexo, 'f') || $sexo === 'femenino') {
            $tmb = 447.593 + (9.247 * floatval($peso)) + (3.098 * floatval($estaturaCm)) - (4.330 * intval($edad));
        } else {
            $tmb = 88.362 + (13.397 * floatval($peso)) + (4.799 * floatval($estaturaCm)) - (5.677 * intval($edad));
        }

        $caloriasMeta = round($tmb * 1.375);

        if ($caloriasMeta < 1200 || $caloriasMeta > 5000) {
            $caloriasMeta = 2000;
        }

        return [
            'calorias' => $caloriasMeta,
        ];
    }

    public function actualizarProgresoObjetivo(Paciente $paciente, ObjetivoNutricional $objetivo, Request $request): JsonResponse
    {
        $usuario = Auth::user();
        if ((int) $paciente->cod_usuarios_pac !== (int) $usuario->cod_usuarios) {
            abort(403, 'No tienes permiso para modificar este objetivo.');
        }

        if ((int) $objetivo->cod_pacientes_obe !== (int) $paciente->cod_pacientes) {
            abort(404, 'Objetivo no encontrado para este paciente.');
        }

        $request->validate([
            'delta' => ['nullable', 'integer', 'between:-10,10'],
            'progreso' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        if ($request->has('progreso')) {
            $nuevoProgreso = max(0, (int) $request->input('progreso'));
            $objetivo->update(['progreso_actual' => $nuevoProgreso]);
        } else {
            $delta = (int) $request->input('delta', 1);
            $objetivo->incrementarProgreso($delta);
        }

        $objetivo->refresh();

        return response()->json([
            'success' => true,
            'progreso_actual' => (int) $objetivo->progreso_actual,
            'meta_progreso' => (int) ($objetivo->meta_progreso ?: 7),
            'unidad_progreso' => $objetivo->unidad_progreso ?: 'veces',
            'porcentaje' => $objetivo->calcularPorcentajeProgreso(),
            'objetivo' => [
                'cod_objetivos_nutricionales' => $objetivo->cod_objetivos_nutricionales,
                'des_obe' => $objetivo->des_obe,
                'est_obe' => $objetivo->est_obe,
                'fec_obe' => optional($objetivo->fec_obe)->format('Y-m-d'),
                'progreso_actual' => (int) $objetivo->progreso_actual,
                'meta_progreso' => (int) ($objetivo->meta_progreso ?: 7),
                'unidad_progreso' => $objetivo->unidad_progreso ?: 'veces',
                'porcentaje' => $objetivo->calcularPorcentajeProgreso(),
            ],
            'mensaje' => 'Progreso actualizado correctamente.',
        ]);
    }
}