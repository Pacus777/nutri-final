<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEvaluacionInicialRequest;
use App\Http\Requests\UpdateEvaluacionInicialRequest;
use App\Models\EvaluacionInicial;
use App\Models\Nutricionista;
use App\Models\Paciente;
use App\Services\NutricionistaPacienteAccessService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EvaluacionInicialController extends Controller
{
    public function __construct(
        private readonly NutricionistaPacienteAccessService $accessService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Flujo administrativo anterior
    |--------------------------------------------------------------------------
    */

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
            ->whereHas('usuario', function ($query) {
                $query->where('est_usu', 'activo');
            })
            ->orderBy('cod_pacientes')
            ->get()
            ->map(fn (Paciente $paciente) => $this->mapPacienteBasico($paciente));

        $nutricionistas = Nutricionista::with('usuario')
            ->where('est_nut', 'activo')
            ->whereHas('usuario', function ($query) {
                $query->where('est_usu', 'activo');
            })
            ->orderBy('cod_nutricionistas')
            ->get()
            ->map(fn (Nutricionista $nutricionista) => $this->mapNutricionistaBasico($nutricionista));

        return Inertia::render('EvaluacionesIniciales/Create', [
            'pacientes' => $pacientes,
            'nutricionistas' => $nutricionistas,
            'estados' => EvaluacionInicial::ESTADOS,
            'pacienteSeleccionado' => $pacienteSeleccionado ?: '',
            'nutricionistaSeleccionado' => $nutricionistaSeleccionado ?: '',
        ]);
    }

    public function store(StoreEvaluacionInicialRequest $request): RedirectResponse
    {
        $paciente = Paciente::findOrFail($request->input('cod_pacientes_evi'));

        if ($this->pacienteTieneEvaluacionVigente($paciente)) {
            return back()->withErrors([
                'cod_pacientes_evi' => 'El paciente ya tiene una evaluación inicial vigente.',
            ])->withInput();
        }

        $evaluacion = new EvaluacionInicial($this->payloadEvaluacion($request));
        $evaluacion->recalcularImc();
        $evaluacion->save();

        return redirect()
            ->route('pacientes.index')
            ->with('success', 'Evaluación inicial registrada correctamente.');
    }

    public function show(EvaluacionInicial $evaluacionInicial): Response
    {
        $evaluacionInicial->load([
            'paciente.usuario.roles',
            'nutricionista.usuario.roles',
        ]);

        return Inertia::render('EvaluacionesIniciales/Show', [
            'evaluacion' => $this->mapEvaluacionCompleta($evaluacionInicial),
            'modo' => 'admin',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Flujo paciente: onboarding inicial
    |--------------------------------------------------------------------------
    */

    public function onboardingPaciente(): Response
    {
        $paciente = $this->pacienteAutenticado();

        $evaluacion = $this->evaluacionVigenteDelPaciente($paciente);

        return Inertia::render('EvaluacionesIniciales/OnboardingPaciente', [
            'paciente' => $this->mapPacienteCompleto($paciente),
            'evaluacion' => $evaluacion ? $this->mapEvaluacionCompleta($evaluacion) : null,
            'estados' => EvaluacionInicial::ESTADOS,
        ]);
    }

    public function guardarOnboardingPaciente(StoreEvaluacionInicialRequest $request): RedirectResponse
    {
        $paciente = $this->pacienteAutenticado();

        $evaluacion = $this->evaluacionVigenteDelPaciente($paciente);

        if (! $evaluacion) {
            $evaluacion = new EvaluacionInicial([
                'cod_pacientes_evi' => $paciente->getKey(),
                'cod_nutricionistas_evi' => null,
                'est_evi' => EvaluacionInicial::ESTADO_BORRADOR,
            ]);
        }

        if (! $evaluacion->puedeEditarse()) {
            return back()->withErrors([
                'evaluacion' => 'La evaluación inicial ya no puede modificarse.',
            ]);
        }

        $evaluacion->fill([
            'fec_evi' => $request->input('fec_evi'),
            'peso_evi' => $request->input('peso_evi'),
            'estatura_evi' => $request->input('estatura_evi'),
            'objetivo_inicial_evi' => $request->input('objetivo_inicial_evi'),
            'restricciones_evi' => $request->input('restricciones_evi'),
            'contexto_general_evi' => $request->input('contexto_general_evi'),
            'obs_evi' => $request->input('obs_evi'),
        ]);

        if (! $evaluacion->est_evi) {
            $evaluacion->est_evi = EvaluacionInicial::ESTADO_BORRADOR;
        }

        $evaluacion->recalcularImc();
        $evaluacion->save();

        return redirect()
            ->route('paciente.evaluacion-inicial.onboarding')
            ->with('success', 'Tu evaluación inicial fue guardada correctamente.');
    }

    public function enviarRevisionPaciente(EvaluacionInicial $evaluacionInicial): RedirectResponse
    {
        $paciente = $this->pacienteAutenticado();

        $this->asegurarEvaluacionPertenecePaciente($evaluacionInicial, $paciente);

        if (! $evaluacionInicial->puedeEditarse()) {
            return back()->withErrors([
                'evaluacion' => 'La evaluación inicial ya no puede enviarse a revisión.',
            ]);
        }

        $evaluacionInicial->marcarPendienteRevision();

        return redirect()
            ->route('paciente.evaluacion-inicial.onboarding')
            ->with('success', 'Tu evaluación inicial fue enviada para revisión.');
    }

    /*
    |--------------------------------------------------------------------------
    | Flujo nutricionista: gestión clínica del paciente asignado
    |--------------------------------------------------------------------------
    */

    public function showPaciente(Paciente $paciente): Response|RedirectResponse
    {
        $this->accessService->asegurarAccesoAPaciente($paciente);

        $evaluacion = $this->evaluacionVigenteDelPaciente($paciente);

        if (! $evaluacion) {
            return redirect()
                ->route('nutricionista.evaluaciones-iniciales.create-paciente', $paciente->getKey())
                ->with('info', 'El paciente aún no tiene evaluación inicial. Puedes registrarla ahora.');
        }

        $evaluacion->load([
            'paciente.usuario.roles',
            'nutricionista.usuario.roles',
        ]);

        return Inertia::render('EvaluacionesIniciales/Show', [
            'evaluacion' => $this->mapEvaluacionCompleta($evaluacion),
            'paciente' => $this->mapPacienteCompleto($paciente),
            'modo' => 'nutricionista',
        ]);
    }

    public function createPaciente(Paciente $paciente): Response|RedirectResponse
    {
        $nutricionista = $this->accessService->asegurarAccesoAPaciente($paciente);

        $evaluacion = $this->evaluacionVigenteDelPaciente($paciente);

        if ($evaluacion) {
            return redirect()
                ->route('nutricionista.evaluaciones-iniciales.show-paciente', $paciente->getKey())
                ->with('info', 'El paciente ya tiene una evaluación inicial vigente.');
        }

        $paciente->load('usuario');

        return Inertia::render('EvaluacionesIniciales/CreatePaciente', [
            'paciente' => $this->mapPacienteCompleto($paciente),
            'nutricionista' => $this->mapNutricionistaBasico($nutricionista),
            'estados' => EvaluacionInicial::ESTADOS,
            'modo' => 'nutricionista',
        ]);
    }

    public function storePaciente(StoreEvaluacionInicialRequest $request, Paciente $paciente): RedirectResponse
    {
        $nutricionista = $this->accessService->asegurarAccesoAPaciente($paciente);

        if ($this->pacienteTieneEvaluacionVigente($paciente)) {
            return back()->withErrors([
                'evaluacion' => 'El paciente ya tiene una evaluación inicial vigente.',
            ])->withInput();
        }

        $evaluacion = new EvaluacionInicial([
            'cod_pacientes_evi' => $paciente->getKey(),
            'cod_nutricionistas_evi' => $nutricionista->getKey(),
            'fec_evi' => $request->input('fec_evi'),
            'est_evi' => EvaluacionInicial::ESTADO_ACTIVA,
            'peso_evi' => $request->input('peso_evi'),
            'estatura_evi' => $request->input('estatura_evi'),
            'objetivo_inicial_evi' => $request->input('objetivo_inicial_evi'),
            'restricciones_evi' => $request->input('restricciones_evi'),
            'contexto_general_evi' => $request->input('contexto_general_evi'),
            'obs_evi' => $request->input('obs_evi'),
        ]);

        $evaluacion->recalcularImc();
        $evaluacion->save();

        return redirect()
            ->route('nutricionista.evaluaciones-iniciales.show-paciente', $paciente->getKey())
            ->with('success', 'Evaluación inicial registrada correctamente.');
    }

    public function editNutricionista(EvaluacionInicial $evaluacionInicial): Response
    {
        $evaluacionInicial->load([
            'paciente.usuario',
            'nutricionista.usuario',
        ]);

        $this->accessService->asegurarAccesoAPaciente($evaluacionInicial->paciente);

        if (! $evaluacionInicial->puedeEditarse()) {
            throw new AuthorizationException('No puedes editar una evaluación inicial cerrada.');
        }

        return Inertia::render('EvaluacionesIniciales/Edit', [
            'evaluacion' => $this->mapEvaluacionCompleta($evaluacionInicial),
            'estados' => EvaluacionInicial::ESTADOS,
            'modo' => 'nutricionista',
        ]);
    }

    public function updateNutricionista(
        UpdateEvaluacionInicialRequest $request,
        EvaluacionInicial $evaluacionInicial
    ): RedirectResponse {
        $evaluacionInicial->load('paciente');

        $nutricionista = $this->accessService->asegurarAccesoAPaciente($evaluacionInicial->paciente);

        if (! $evaluacionInicial->puedeEditarse()) {
            return back()->withErrors([
                'evaluacion' => 'No puedes editar una evaluación inicial cerrada.',
            ]);
        }

        $evaluacionInicial->fill([
            'cod_nutricionistas_evi' => $evaluacionInicial->cod_nutricionistas_evi ?: $nutricionista->getKey(),
            'fec_evi' => $request->input('fec_evi'),
            'peso_evi' => $request->input('peso_evi'),
            'estatura_evi' => $request->input('estatura_evi'),
            'objetivo_inicial_evi' => $request->input('objetivo_inicial_evi'),
            'restricciones_evi' => $request->input('restricciones_evi'),
            'contexto_general_evi' => $request->input('contexto_general_evi'),
            'obs_evi' => $request->input('obs_evi'),
        ]);

        $evaluacionInicial->recalcularImc();
        $evaluacionInicial->save();

        return redirect()
            ->route('nutricionista.evaluaciones-iniciales.show-paciente', $evaluacionInicial->cod_pacientes_evi)
            ->with('success', 'Evaluación inicial actualizada correctamente.');
    }

    public function aprobarNutricionista(EvaluacionInicial $evaluacionInicial): RedirectResponse
    {
        $evaluacionInicial->load('paciente');

        $nutricionista = $this->accessService->asegurarAccesoAPaciente($evaluacionInicial->paciente);

        if ($evaluacionInicial->estaCerrada()) {
            return back()->withErrors([
                'evaluacion' => 'No puedes aprobar una evaluación inicial cerrada.',
            ]);
        }

        $evaluacionInicial->update([
            'cod_nutricionistas_evi' => $nutricionista->getKey(),
            'est_evi' => EvaluacionInicial::ESTADO_ACTIVA,
        ]);

        return redirect()
            ->route('nutricionista.evaluaciones-iniciales.show-paciente', $evaluacionInicial->cod_pacientes_evi)
            ->with('success', 'Evaluación inicial aprobada correctamente.');
    }

    public function cerrarNutricionista(EvaluacionInicial $evaluacionInicial): RedirectResponse
    {
        $evaluacionInicial->load('paciente');

        $this->accessService->asegurarAccesoAPaciente($evaluacionInicial->paciente);

        if ($evaluacionInicial->estaCerrada()) {
            return back()->withErrors([
                'evaluacion' => 'La evaluación inicial ya está cerrada.',
            ]);
        }

        $evaluacionInicial->cerrar();

        return redirect()
            ->route('nutricionista.evaluaciones-iniciales.show-paciente', $evaluacionInicial->cod_pacientes_evi)
            ->with('success', 'Evaluación inicial cerrada correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers privados
    |--------------------------------------------------------------------------
    */

    private function pacienteAutenticado(): Paciente
    {
        $usuario = Auth::user();

        if (! $usuario) {
            throw new AuthorizationException('Debes iniciar sesión.');
        }

        $paciente = Paciente::with('usuario')
            ->where('cod_usuarios_pac', $usuario->getKey())
            ->where('est_pac', 'activo')
            ->first();

        if (! $paciente) {
            throw new AuthorizationException('No se encontró un perfil de paciente activo.');
        }

        return $paciente;
    }

    private function asegurarEvaluacionPertenecePaciente(
        EvaluacionInicial $evaluacion,
        Paciente $paciente
    ): void {
        if ((int) $evaluacion->cod_pacientes_evi !== (int) $paciente->getKey()) {
            throw new AuthorizationException('No puedes acceder a esta evaluación inicial.');
        }
    }

    private function evaluacionVigenteDelPaciente(Paciente $paciente): ?EvaluacionInicial
    {
        return EvaluacionInicial::query()
            ->where('cod_pacientes_evi', $paciente->getKey())
            ->vigentes()
            ->with([
                'paciente.usuario',
                'nutricionista.usuario',
            ])
            ->latest('cod_evaluaciones_iniciales')
            ->first();
    }

    private function pacienteTieneEvaluacionVigente(Paciente $paciente): bool
    {
        return EvaluacionInicial::query()
            ->where('cod_pacientes_evi', $paciente->getKey())
            ->vigentes()
            ->exists();
    }

    private function payloadEvaluacion(StoreEvaluacionInicialRequest $request): array
    {
        return [
            'cod_pacientes_evi' => $request->input('cod_pacientes_evi'),
            'cod_nutricionistas_evi' => $request->input('cod_nutricionistas_evi'),
            'fec_evi' => $request->input('fec_evi'),
            'est_evi' => $request->input('est_evi', EvaluacionInicial::ESTADO_ACTIVA),
            'peso_evi' => $request->input('peso_evi'),
            'estatura_evi' => $request->input('estatura_evi'),
            'objetivo_inicial_evi' => $request->input('objetivo_inicial_evi'),
            'restricciones_evi' => $request->input('restricciones_evi'),
            'contexto_general_evi' => $request->input('contexto_general_evi'),
            'obs_evi' => $request->input('obs_evi'),
        ];
    }

    private function mapEvaluacionCompleta(EvaluacionInicial $evaluacion): array
    {
        return [
            'cod_evaluaciones_iniciales' => $evaluacion->cod_evaluaciones_iniciales,
            'cod_pacientes_evi' => $evaluacion->cod_pacientes_evi,
            'cod_nutricionistas_evi' => $evaluacion->cod_nutricionistas_evi,
            'fec_evi' => optional($evaluacion->fec_evi)->format('Y-m-d'),
            'est_evi' => $evaluacion->est_evi,
            'peso_evi' => $evaluacion->peso_evi,
            'estatura_evi' => $evaluacion->estatura_evi,
            'imc_evi' => $evaluacion->imc_evi,
            'objetivo_inicial_evi' => $evaluacion->objetivo_inicial_evi,
            'restricciones_evi' => $evaluacion->restricciones_evi,
            'contexto_general_evi' => $evaluacion->contexto_general_evi,
            'obs_evi' => $evaluacion->obs_evi,
            'paciente' => $evaluacion->paciente
                ? $this->mapPacienteCompleto($evaluacion->paciente)
                : null,
            'nutricionista' => $evaluacion->nutricionista
                ? $this->mapNutricionistaBasico($evaluacion->nutricionista)
                : null,
        ];
    }

    private function mapPacienteBasico(Paciente $paciente): array
    {
        return [
            'cod_pacientes' => $paciente->cod_pacientes,
            'usuario' => $paciente->usuario ? [
                'nom_usu' => $paciente->usuario->nom_usu,
                'ape_usu' => $paciente->usuario->ape_usu,
                'nombre_completo' => $paciente->usuario->nombre_completo,
                'cor_usu' => $paciente->usuario->cor_usu,
            ] : null,
        ];
    }

    private function mapPacienteCompleto(Paciente $paciente): array
    {
        $paciente->loadMissing('usuario');

        return [
            'cod_pacientes' => $paciente->cod_pacientes,
            'sex_pac' => $paciente->sex_pac,
            'pes_pac' => $paciente->pes_pac,
            'estatura_pac' => $paciente->estatura_pac,
            'tip_san_pac' => $paciente->tip_san_pac,
            'ale_pac' => $paciente->ale_pac,
            'est_pac' => $paciente->est_pac,
            'usuario' => $paciente->usuario ? [
                'nom_usu' => $paciente->usuario->nom_usu,
                'ape_usu' => $paciente->usuario->ape_usu,
                'nombre_completo' => $paciente->usuario->nombre_completo,
                'cor_usu' => $paciente->usuario->cor_usu,
                'ci_usu' => $paciente->usuario->ci_usu,
                'ext_ci_usu' => $paciente->usuario->ext_ci_usu,
            ] : null,
        ];
    }

    private function mapNutricionistaBasico(Nutricionista $nutricionista): array
    {
        $nutricionista->loadMissing('usuario');

        return [
            'cod_nutricionistas' => $nutricionista->cod_nutricionistas,
            'esp_nut' => $nutricionista->esp_nut,
            'est_nut' => $nutricionista->est_nut,
            'usuario' => $nutricionista->usuario ? [
                'nom_usu' => $nutricionista->usuario->nom_usu,
                'ape_usu' => $nutricionista->usuario->ape_usu,
                'nombre_completo' => $nutricionista->usuario->nombre_completo,
                'cor_usu' => $nutricionista->usuario->cor_usu,
                'ci_usu' => $nutricionista->usuario->ci_usu,
                'ext_ci_usu' => $nutricionista->usuario->ext_ci_usu,
            ] : null,
        ];
    }
}