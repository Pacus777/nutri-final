<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlanNutricionalRequest;
use App\Http\Requests\UpdatePlanNutricionalRequest;
use App\Models\Nutricionista;
use App\Models\Paciente;
use App\Models\PlanNutricional;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanNutricionalController extends Controller
{
    public function create(Request $request): Response
    {
        $pacienteSeleccionado = $request->integer('paciente');
        $nutricionistaSeleccionado = '';

        if ($pacienteSeleccionado) {
            $paciente = Paciente::with('asignacionActiva')->find($pacienteSeleccionado);

            if ($paciente && $paciente->asignacionActiva) {
                $nutricionistaSeleccionado = $paciente->asignacionActiva->cod_nutricionistas_anp;
            }
        }

        $pacientes = Paciente::with('usuario')
            ->where('est_pac', 'activo')
            ->whereHas('usuario', fn ($q) => $q->where('est_usu', 'activo'))
            ->orderBy('cod_pacientes')
            ->get()
            ->map(fn ($paciente) => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'usuario' => [
                    'nombre_completo' => $paciente->usuario->nombre_completo,
                    'cor_usu' => $paciente->usuario->cor_usu,
                ],
            ]);

        $nutricionistas = Nutricionista::with('usuario')
            ->where('est_nut', 'activo')
            ->whereHas('usuario', fn ($q) => $q->where('est_usu', 'activo'))
            ->orderBy('cod_nutricionistas')
            ->get()
            ->map(fn ($nutricionista) => [
                'cod_nutricionistas' => $nutricionista->cod_nutricionistas,
                'usuario' => [
                    'nombre_completo' => $nutricionista->usuario->nombre_completo,
                    'cor_usu' => $nutricionista->usuario->cor_usu,
                ],
            ]);

        return Inertia::render('PlanesNutricionales/Create', [
            'pacientes' => $pacientes,
            'nutricionistas' => $nutricionistas,
            'estados' => ['vigente', 'cerrado', 'reemplazado'],
            'pacienteSeleccionado' => $pacienteSeleccionado ?: '',
            'nutricionistaSeleccionado' => $nutricionistaSeleccionado ?: '',
        ]);
    }

    public function store(StorePlanNutricionalRequest $request): RedirectResponse
    {
        $planVigente = PlanNutricional::where('cod_pacientes_pan', $request->cod_pacientes_pan)
            ->where('est_pan', 'vigente')
            ->exists();

        if ($request->est_pan === 'vigente' && $planVigente) {
            return back()->withErrors([
                'cod_pacientes_pan' => 'El paciente ya tiene un plan nutricional vigente.',
            ])->withInput();
        }

        PlanNutricional::create([
            'cod_pacientes_pan' => $request->cod_pacientes_pan,
            'cod_nutricionistas_pan' => $request->cod_nutricionistas_pan,
            'fec_pan' => $request->fec_pan,
            'des_pan' => $request->des_pan,
            'est_pan' => $request->est_pan,
        ]);

        return redirect()
            ->route('planes-nutricionales.vigente', $request->cod_pacientes_pan)
            ->with('success', 'Plan nutricional registrado correctamente.');
    }

    public function vigente(Paciente $paciente): Response
    {
        $paciente->load('usuario');

        $plan = $paciente->planNutricionalVigente()
            ->with('nutricionista.usuario')
            ->first();

        return Inertia::render('PlanesNutricionales/Vigente', [
            'paciente' => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'usuario' => $paciente->usuario ? [
                    'nombre_completo' => $paciente->usuario->nombre_completo,
                    'cor_usu' => $paciente->usuario->cor_usu,
                    'ci_usu' => $paciente->usuario->ci_usu,
                    'ext_ci_usu' => $paciente->usuario->ext_ci_usu,
                ] : null,
            ],
            'plan' => $plan ? [
                'cod_planes_nutricionales' => $plan->cod_planes_nutricionales,
                'fec_pan' => optional($plan->fec_pan)->format('Y-m-d'),
                'des_pan' => $plan->des_pan,
                'est_pan' => $plan->est_pan,
                'nutricionista' => $plan->nutricionista ? [
                    'cod_nutricionistas' => $plan->nutricionista->cod_nutricionistas,
                    'usuario' => $plan->nutricionista->usuario ? [
                        'nombre_completo' => $plan->nutricionista->usuario->nombre_completo,
                        'cor_usu' => $plan->nutricionista->usuario->cor_usu,
                    ] : null,
                ] : null,
            ] : null,
        ]);
    }

    public function show(PlanNutricional $planNutricional): Response
    {
        $planNutricional->load(['paciente.usuario', 'nutricionista.usuario']);

        return Inertia::render('PlanesNutricionales/Show', [
            'plan' => [
                'cod_planes_nutricionales' => $planNutricional->cod_planes_nutricionales,
                'cod_pacientes_pan' => $planNutricional->cod_pacientes_pan,
                'cod_nutricionistas_pan' => $planNutricional->cod_nutricionistas_pan,
                'fec_pan' => optional($planNutricional->fec_pan)->format('Y-m-d'),
                'des_pan' => $planNutricional->des_pan,
                'est_pan' => $planNutricional->est_pan,
                'paciente' => $planNutricional->paciente ? [
                    'cod_pacientes' => $planNutricional->paciente->cod_pacientes,
                    'usuario' => $planNutricional->paciente->usuario ? [
                        'nombre_completo' => $planNutricional->paciente->usuario->nombre_completo,
                        'cor_usu' => $planNutricional->paciente->usuario->cor_usu,
                        'ci_usu' => $planNutricional->paciente->usuario->ci_usu,
                        'ext_ci_usu' => $planNutricional->paciente->usuario->ext_ci_usu,
                    ] : null,
                ] : null,
                'nutricionista' => $planNutricional->nutricionista ? [
                    'cod_nutricionistas' => $planNutricional->nutricionista->cod_nutricionistas,
                    'esp_nut' => $planNutricional->nutricionista->esp_nut,
                    'usuario' => $planNutricional->nutricionista->usuario ? [
                        'nombre_completo' => $planNutricional->nutricionista->usuario->nombre_completo,
                        'cor_usu' => $planNutricional->nutricionista->usuario->cor_usu,
                        'ci_usu' => $planNutricional->nutricionista->usuario->ci_usu,
                        'ext_ci_usu' => $planNutricional->nutricionista->usuario->ext_ci_usu,
                    ] : null,
                ] : null,
            ],
        ]);
    }

    public function edit(PlanNutricional $planNutricional): Response
    {
        $planNutricional->load(['paciente.usuario', 'nutricionista.usuario']);

        return Inertia::render('PlanesNutricionales/Edit', [
            'plan' => [
                'cod_planes_nutricionales' => $planNutricional->cod_planes_nutricionales,
                'cod_pacientes_pan' => $planNutricional->cod_pacientes_pan,
                'cod_nutricionistas_pan' => $planNutricional->cod_nutricionistas_pan,
                'fec_pan' => optional($planNutricional->fec_pan)->format('Y-m-d'),
                'des_pan' => $planNutricional->des_pan,
                'est_pan' => $planNutricional->est_pan,
            ],
            'estados' => ['activo', 'vigente', 'pausado', 'finalizado', 'reemplazado', 'cancelado'],
        ]);
    }

    public function update(PlanNutricional $planNutricional, UpdatePlanNutricionalRequest $request): RedirectResponse
    {
        $planNutricional->update($request->validated());

        return redirect()
            ->route('planes-nutricionales.show', $planNutricional)
            ->with('success', 'Plan nutricional actualizado correctamente.');
    }

    public function cerrar(PlanNutricional $planNutricional): RedirectResponse
    {
        $planNutricional->cerrar();

        return redirect()
            ->route('planes-nutricionales.show', $planNutricional)
            ->with('success', 'Plan nutricional cerrado correctamente.');
    }

    public function reemplazar(PlanNutricional $planNutricional): RedirectResponse
    {
        $planNutricional->reemplazar();

        return redirect()
            ->route('planes-nutricionales.show', $planNutricional)
            ->with('success', 'Plan nutricional marcado como reemplazado.');
    }

    public function porPaciente(Paciente $paciente): Response
    {
        $paciente->load('usuario');

        $planes = $paciente->planesNutricionales()
            ->with('nutricionista.usuario')
            ->latest('fec_pan')
            ->get()
            ->map(fn ($plan) => [
                'cod_planes_nutricionales' => $plan->cod_planes_nutricionales,
                'fec_pan' => optional($plan->fec_pan)->format('Y-m-d'),
                'des_pan' => $plan->des_pan,
                'est_pan' => $plan->est_pan,
                'nutricionista' => $plan->nutricionista ? [
                    'cod_nutricionistas' => $plan->nutricionista->cod_nutricionistas,
                    'usuario' => $plan->nutricionista->usuario ? [
                        'nombre_completo' => $plan->nutricionista->usuario->nombre_completo,
                        'cor_usu' => $plan->nutricionista->usuario->cor_usu,
                    ] : null,
                ] : null,
            ]);

        return Inertia::render('PlanesNutricionales/PorPaciente', [
            'paciente' => [
                'cod_pacientes' => $paciente->cod_pacientes,
                'usuario' => $paciente->usuario ? [
                    'nombre_completo' => $paciente->usuario->nombre_completo,
                    'cor_usu' => $paciente->usuario->cor_usu,
                    'ci_usu' => $paciente->usuario->ci_usu,
                    'ext_ci_usu' => $paciente->usuario->ext_ci_usu,
                ] : null,
            ],
            'planes' => $planes,
        ]);
    }
}