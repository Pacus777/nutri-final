<?php

namespace App\Http\Controllers\Nutricionista;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSeguimientoNutricionalRequest;
use App\Models\ContextoPaciente;
use App\Models\HistorialNutricional;
use App\Models\Nutricionista;
use App\Models\Paciente;
use App\Models\SeguimientoNutricional;
use App\Services\AnalisisInteligentePacienteService;
use App\Services\SeguimientoNutricionalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SeguimientoNutricionalController extends Controller
{
    public function __construct(
        protected SeguimientoNutricionalService $seguimientoService,
        protected AnalisisInteligentePacienteService $analisisInteligenteService
    ) {
    }

    public function store(StoreSeguimientoNutricionalRequest $request): RedirectResponse
    {
        $nutricionista = $this->obtenerNutricionistaAutenticado();

        $data = $request->validated();

        if ((int) $data['cod_nutricionistas_seg'] !== (int) $nutricionista->cod_nutricionistas) {
            return back()->withErrors([
                'cod_nutricionistas_seg' => 'No puedes registrar seguimiento en nombre de otro nutricionista.',
            ]);
        }

        $this->seguimientoService->registrar($data, $nutricionista);

        return back()
            ->with('success', 'Seguimiento nutricional registrado correctamente.');
    }

    public function create(Paciente $paciente): Response
    {
        $nutricionista = $this->obtenerNutricionistaAutenticado();

        $this->verificarAccesoPaciente($paciente, $nutricionista);

        $paciente->load('usuario');

        $contextosCriticos = $paciente->contextosPaciente()
            ->activos()
            ->altaPrioridad()
            ->recientes()
            ->get();

        return Inertia::render('SeguimientosNutricionales/Create', [
            'paciente' => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'usuario' => [
                    'nombre_completo' => $paciente->usuario?->nombre_completo,
                    'cor_usu' => $paciente->usuario?->cor_usu,
                ],
            ],
            'nutricionista' => [
                'cod_nutricionistas' => $nutricionista->cod_nutricionistas,
            ],
            'fechaSugerida' => now()->toDateString(),
            'contextosCriticos' => $contextosCriticos
                ->map(fn (ContextoPaciente $contexto) => $this->mapContextoCriticoSeguimiento($contexto))
                ->values(),
            'analisisInteligente' => $this->analisisInteligenteService->generar($paciente),
        ]);
    }

    public function historial(Paciente $paciente): Response
    {
        $nutricionista = $this->obtenerNutricionistaAutenticado();

        $this->verificarAccesoPaciente($paciente, $nutricionista);

        $paciente->load('usuario');

        $seguimientos = SeguimientoNutricional::query()
            ->where('cod_pacientes_seg', $paciente->cod_pacientes)
            ->where('cod_nutricionistas_seg', $nutricionista->cod_nutricionistas)
            ->orderByDesc('fec_seg')
            ->orderByDesc('cod_seguimientos_nutricionales')
            ->get()
            ->map(fn (SeguimientoNutricional $seguimiento) => [
                'cod_seguimientos_nutricionales' => $seguimiento->cod_seguimientos_nutricionales,
                'fec_seg' => optional($seguimiento->fec_seg)->format('Y-m-d'),
                'res_seg' => $seguimiento->res_seg,
                'cam_seg' => $seguimiento->cam_seg,
                'tiene_cambio_significativo' => $seguimiento->tieneCambioSignificativo(),
            ])
            ->values();

        return Inertia::render('SeguimientosNutricionales/Historial', [
            'paciente' => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'usuario' => [
                    'nombre_completo' => $paciente->usuario?->nombre_completo,
                    'cor_usu' => $paciente->usuario?->cor_usu,
                ],
            ],
            'seguimientos' => $seguimientos,
        ]);
    }

    public function comparativo(Paciente $paciente, Request $request): Response
    {
        $nutricionista = $this->obtenerNutricionistaAutenticado();

        $this->verificarAccesoPaciente($paciente, $nutricionista);

        $paciente->load('usuario');

        $seguimientos = SeguimientoNutricional::query()
            ->where('cod_pacientes_seg', $paciente->cod_pacientes)
            ->where('cod_nutricionistas_seg', $nutricionista->cod_nutricionistas)
            ->orderByDesc('fec_seg')
            ->get();

        $fechaA = (string) $request->input('fecha_a', '');
        $fechaB = (string) $request->input('fecha_b', '');

        $segA = $seguimientos->first(fn ($item) => optional($item->fec_seg)->format('Y-m-d') === $fechaA);
        $segB = $seguimientos->first(fn ($item) => optional($item->fec_seg)->format('Y-m-d') === $fechaB);

        $comparacion = null;

        if ($segA && $segB) {
            similar_text(
                mb_strtolower(trim((string) $segA->res_seg)),
                mb_strtolower(trim((string) $segB->res_seg)),
                $similitud
            );

            $comparacion = [
                'similitud' => round((float) $similitud, 2),
                'hubo_cambio_significativo' => $similitud < 65,
                'seguimiento_a' => [
                    'fec_seg' => optional($segA->fec_seg)->format('Y-m-d'),
                    'res_seg' => $segA->res_seg,
                    'cam_seg' => $segA->cam_seg,
                ],
                'seguimiento_b' => [
                    'fec_seg' => optional($segB->fec_seg)->format('Y-m-d'),
                    'res_seg' => $segB->res_seg,
                    'cam_seg' => $segB->cam_seg,
                ],
            ];
        }

        // --- EXTRACCIÓN DE DATOS DE EVOLUCIÓN NUTRICIONAL PARA GRÁFICAS ---
        $fechaInicio = $fechaA && $fechaB ? min($fechaA, $fechaB) : \Carbon\Carbon::now()->subDays(30)->toDateString();
        $fechaFin = $fechaA && $fechaB ? max($fechaA, $fechaB) : \Carbon\Carbon::now()->toDateString();

        // En caso de que el rango sea de menos de 15 días, ampliamos hacia atrás para tener una curva decente
        if (\Carbon\Carbon::parse($fechaInicio)->diffInDays(\Carbon\Carbon::parse($fechaFin)) < 15) {
            $fechaInicio = \Carbon\Carbon::parse($fechaFin)->subDays(15)->toDateString();
        }

        $registros = \App\Models\RegistroAlimenticio::query()
            ->where('cod_pacientes_ral', $paciente->cod_pacientes)
            ->whereBetween('fec_ral', [$fechaInicio, $fechaFin])
            ->with('detalles.alimento.composicionVigente')
            ->orderBy('fec_ral')
            ->get();

        $datosGrafica = [];
        foreach ($registros as $registro) {
            $cal = 0;
            $pro = 0;
            $car = 0;
            $gra = 0;

            foreach ($registro->detalles as $detalle) {
                $composicion = $detalle->alimento?->composicionVigente;
                if ($composicion) {
                    $cantidad = max((float) $detalle->can_dra, 0);
                    $unidad = (string) $detalle->uni_dra;
                    $factor = 1.0;

                    if ($cantidad > 0) {
                        if (in_array($unidad, ['gramos', 'mililitros'], true)) {
                            $factor = $cantidad / 100;
                        }
                    } else {
                        $factor = 0;
                    }

                    $cal += (float) $composicion->cal_tcn * $factor;
                    $pro += (float) $composicion->pro_tcn * $factor;
                    $car += (float) $composicion->car_tcn * $factor;
                    $gra += (float) $composicion->gra_tcn * $factor;
                }
            }

            $datosGrafica[] = [
                'fecha' => optional($registro->fec_ral)->format('Y-m-d'),
                'calorias' => round($cal, 1),
                'proteinas' => round($pro, 1),
                'carbohidratos' => round($car, 1),
                'grasas' => round($gra, 1),
            ];
        }

        $metasNutricionales = $this->calcularMetasNutricionales($paciente);

        return Inertia::render('SeguimientosNutricionales/Comparativo', [
            'paciente' => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'usuario' => [
                    'nombre_completo' => $paciente->usuario?->nombre_completo,
                    'cor_usu' => $paciente->usuario?->cor_usu,
                ],
            ],
            'fechasDisponibles' => $seguimientos
                ->map(fn ($item) => optional($item->fec_seg)->format('Y-m-d'))
                ->values(),
            'filtros' => [
                'fecha_a' => $fechaA,
                'fecha_b' => $fechaB,
            ],
            'comparacion' => $comparacion,
            'datosGrafica' => $datosGrafica,
            'metasNutricionales' => $metasNutricionales,
        ]);
    }

    public function consolidado(Paciente $paciente): Response
    {
        $nutricionista = $this->obtenerNutricionistaAutenticado();

        $this->verificarAccesoPaciente($paciente, $nutricionista);

        $paciente->load('usuario');

        $seguimientos = SeguimientoNutricional::query()
            ->where('cod_pacientes_seg', $paciente->cod_pacientes)
            ->where('cod_nutricionistas_seg', $nutricionista->cod_nutricionistas)
            ->orderByDesc('fec_seg')
            ->get();

        $historial = HistorialNutricional::query()
            ->where('cod_pacientes_hin', $paciente->cod_pacientes)
            ->orderByDesc('fec_hin')
            ->limit(12)
            ->get()
            ->map(fn ($item) => [
                'fec_hin' => optional($item->fec_hin)->format('Y-m-d'),
                'des_hin' => $item->des_hin,
            ])
            ->values();

        return Inertia::render('SeguimientosNutricionales/Consolidado', [
            'paciente' => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'usuario' => [
                    'nombre_completo' => $paciente->usuario?->nombre_completo,
                    'cor_usu' => $paciente->usuario?->cor_usu,
                ],
            ],
            'resumen' => [
                'total_seguimientos' => $seguimientos->count(),
                'cambios_significativos' => $seguimientos->filter(fn (SeguimientoNutricional $s) => $s->tieneCambioSignificativo())->count(),
                'ultimo_seguimiento' => optional($seguimientos->first()?->fec_seg)->format('Y-m-d'),
                'total_historial' => $historial->count(),
            ],
            'historial' => $historial,
        ]);
    }

    protected function obtenerNutricionistaAutenticado(): Nutricionista
    {
        $usuario = Auth::user();

        if (! $usuario) {
            abort(403, 'No existe una sesion de usuario activa.');
        }

        $nutricionista = Nutricionista::where('cod_usuarios_nut', $usuario->cod_usuarios)
            ->where('est_nut', 'activo')
            ->first();

        if (! $nutricionista) {
            abort(403, 'No existe un perfil de nutricionista activo para el usuario autenticado.');
        }

        return $nutricionista;
    }

    protected function verificarAccesoPaciente(Paciente $paciente, Nutricionista $nutricionista): void
    {
        $tieneAsignacionActiva = $paciente->asignacionesNutricionista()
            ->where('cod_nutricionistas_anp', $nutricionista->cod_nutricionistas)
            ->where('est_anp', 'activa')
            ->exists();

        if (! $tieneAsignacionActiva) {
            abort(403, 'No tienes una asignacion activa para registrar seguimiento de este paciente.');
        }
    }

    protected function mapContextoCriticoSeguimiento(ContextoPaciente $contexto): array
    {
        return [
            'cod_contextos_paciente' => $contexto->cod_contextos_paciente,
            'tip_cxp' => $contexto->tip_cxp,
            'pri_cxp' => $contexto->pri_cxp,
            'des_cxp' => $contexto->des_cxp,
            'fec_cxp' => optional($contexto->fec_cxp)->format('Y-m-d'),
            'est_cxp' => $contexto->est_cxp,
            'es_critico' => $contexto->esClinicamenteCritico(),
        ];
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

        $carbohidratosMeta = round(($caloriasMeta * 0.55) / 4);
        $proteinaMeta = round(($caloriasMeta * 0.15) / 4);
        $grasaMeta = round(($caloriasMeta * 0.30) / 9);

        return [
            'calorias' => $caloriasMeta,
            'proteina' => $proteinaMeta,
            'carbohidratos' => $carbohidratosMeta,
            'grasa' => $grasaMeta,
        ];
    }
}
