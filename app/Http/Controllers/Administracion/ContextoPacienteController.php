<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContextoPacienteRequest;
use App\Http\Requests\UpdateContextoPacienteRequest;
use App\Models\ContextoPaciente;
use App\Models\Paciente;
use App\Services\NutricionistaPacienteAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContextoPacienteController extends Controller
{
    public function __construct(
        private readonly NutricionistaPacienteAccessService $accessService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Flujo general: Admin / SuperAdmin / compatibilidad
    |--------------------------------------------------------------------------
    */

    public function index(): Response
    {
        $pacientesConContextos = $this->pacientesPermitidosQuery()
            ->whereHas('contextosPaciente')
            ->with([
                'usuario',
                'contextosPaciente' => fn ($query) => $query->recientes(),
            ])
            ->orderBy('cod_pacientes')
            ->get()
            ->map(fn (Paciente $paciente) => $this->mapPacienteConContextos($paciente))
            ->values();

        return Inertia::render('ContextosPaciente/Index', [
            'pacientes' => $pacientesConContextos,
            'modo' => $this->esNutricionista() ? 'nutricionista' : 'admin',
        ]);
    }

    public function create(Request $request): Response
    {
        $pacienteSeleccionado = $request->integer('paciente');

        $pacientes = $this->pacientesPermitidosQuery()
            ->with('usuario')
            ->orderBy('cod_pacientes')
            ->get()
            ->map(fn (Paciente $paciente) => $this->mapPacienteBasico($paciente))
            ->values();

        return Inertia::render('ContextosPaciente/Create', [
            'pacientes' => $pacientes,
            'pacienteSeleccionado' => $pacienteSeleccionado ?: '',
            'estados' => ContextoPaciente::ESTADOS,
            'tipos' => ContextoPaciente::TIPOS,
            'prioridades' => ContextoPaciente::PRIORIDADES,
            'modo' => $this->esNutricionista() ? 'nutricionista' : 'admin',
            'action' => route('contextos-paciente.store'),
            'volverUrl' => route('contextos-paciente.index'),
        ]);
    }

    public function store(StoreContextoPacienteRequest $request): RedirectResponse
    {
        $paciente = Paciente::findOrFail($request->input('cod_pacientes_cxp'));

        if ($this->esNutricionista()) {
            $this->accessService->asegurarAccesoAPaciente($paciente);
        }

        $contexto = ContextoPaciente::create([
            'cod_pacientes_cxp' => $paciente->getKey(),
            'des_cxp' => $request->input('des_cxp'),
            'fec_cxp' => $request->input('fec_cxp'),
            'tip_cxp' => $request->input('tip_cxp'),
            'pri_cxp' => $request->input('pri_cxp', ContextoPaciente::PRIORIDAD_MEDIA),
            'est_cxp' => $request->input('est_cxp', ContextoPaciente::ESTADO_ACTIVO),
        ]);

        return redirect()
            ->route($this->rutaHistorialContextos(), $contexto->cod_pacientes_cxp)
            ->with('success', 'Contexto del paciente registrado correctamente.');
    }

    public function historial(Paciente $paciente): Response
    {
        if ($this->esNutricionista()) {
            $this->accessService->asegurarAccesoAPaciente($paciente);
        }

        return $this->renderHistorial($paciente, $this->esNutricionista() ? 'nutricionista' : 'admin');
    }

    public function edit(ContextoPaciente $contextoPaciente): Response
    {
        $contextoPaciente->load('paciente.usuario');

        if ($this->esNutricionista()) {
            $this->accessService->asegurarAccesoAPaciente($contextoPaciente->paciente);
        }

        return Inertia::render('ContextosPaciente/Edit', [
            'contexto' => $this->mapContextoCompleto($contextoPaciente),
            'estados' => ContextoPaciente::ESTADOS,
            'tipos' => ContextoPaciente::TIPOS,
            'prioridades' => ContextoPaciente::PRIORIDADES,
            'modo' => $this->esNutricionista() ? 'nutricionista' : 'admin',
            'action' => route('contextos-paciente.update', $contextoPaciente->getKey()),
            'volverUrl' => route('contextos-paciente.historial', $contextoPaciente->cod_pacientes_cxp),
        ]);
    }

    public function update(
        UpdateContextoPacienteRequest $request,
        ContextoPaciente $contextoPaciente
    ): RedirectResponse {
        $contextoPaciente->load('paciente');

        if ($this->esNutricionista()) {
            $this->accessService->asegurarAccesoAPaciente($contextoPaciente->paciente);
        }

        $contextoPaciente->update([
            'des_cxp' => $request->input('des_cxp'),
            'fec_cxp' => $request->input('fec_cxp'),
            'tip_cxp' => $request->input('tip_cxp'),
            'pri_cxp' => $request->input('pri_cxp', ContextoPaciente::PRIORIDAD_MEDIA),
            'est_cxp' => $request->input('est_cxp', ContextoPaciente::ESTADO_ACTIVO),
        ]);

        return redirect()
            ->route($this->rutaHistorialContextos(), $contextoPaciente->cod_pacientes_cxp)
            ->with('success', 'Contexto del paciente actualizado correctamente.');
    }

    public function inactivar(ContextoPaciente $contextoPaciente): RedirectResponse
    {
        $contextoPaciente->load('paciente');

        if ($this->esNutricionista()) {
            $this->accessService->asegurarAccesoAPaciente($contextoPaciente->paciente);
        }

        $contextoPaciente->inactivar();

        return redirect()
            ->route($this->rutaHistorialContextos(), $contextoPaciente->cod_pacientes_cxp)
            ->with('success', 'Contexto del paciente inactivado correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | Flujo clínico del nutricionista
    |--------------------------------------------------------------------------
    */

    public function historialNutricionista(Paciente $paciente): Response
    {
        $this->accessService->asegurarAccesoAPaciente($paciente);

        return $this->renderHistorial($paciente, 'nutricionista');
    }

    public function createPaciente(Paciente $paciente): Response
    {
        $this->accessService->asegurarAccesoAPaciente($paciente);

        $paciente->load('usuario');

        return Inertia::render('ContextosPaciente/Create', [
            'pacientes' => [
                $this->mapPacienteBasico($paciente),
            ],
            'pacienteSeleccionado' => $paciente->getKey(),
            'paciente' => $this->mapPacienteBasico($paciente),
            'estados' => ContextoPaciente::ESTADOS,
            'tipos' => ContextoPaciente::TIPOS,
            'prioridades' => ContextoPaciente::PRIORIDADES,
            'modo' => 'nutricionista',
            'action' => route('nutricionista.contextos-paciente.store-paciente', $paciente->getKey()),
            'volverUrl' => route('nutricionista.pacientes.show', $paciente->getKey()),
        ]);
    }

    public function storePaciente(
        StoreContextoPacienteRequest $request,
        Paciente $paciente
    ): RedirectResponse {
        $this->accessService->asegurarAccesoAPaciente($paciente);

        ContextoPaciente::create([
            'cod_pacientes_cxp' => $paciente->getKey(),
            'des_cxp' => $request->input('des_cxp'),
            'fec_cxp' => $request->input('fec_cxp'),
            'tip_cxp' => $request->input('tip_cxp'),
            'pri_cxp' => $request->input('pri_cxp', ContextoPaciente::PRIORIDAD_MEDIA),
            'est_cxp' => $request->input('est_cxp', ContextoPaciente::ESTADO_ACTIVO),
        ]);

        return redirect()
            ->route('nutricionista.contextos-paciente.historial', $paciente->getKey())
            ->with('success', 'Contexto clínico registrado correctamente.');
    }

    public function editNutricionista(ContextoPaciente $contextoPaciente): Response
    {
        $contextoPaciente->load('paciente.usuario');

        $this->accessService->asegurarAccesoAPaciente($contextoPaciente->paciente);

        return Inertia::render('ContextosPaciente/Edit', [
            'contexto' => $this->mapContextoCompleto($contextoPaciente),
            'estados' => ContextoPaciente::ESTADOS,
            'tipos' => ContextoPaciente::TIPOS,
            'prioridades' => ContextoPaciente::PRIORIDADES,
            'modo' => 'nutricionista',
            'action' => route('nutricionista.contextos-paciente.update', $contextoPaciente->getKey()),
            'volverUrl' => route('nutricionista.contextos-paciente.historial', $contextoPaciente->cod_pacientes_cxp),
        ]);
    }

    public function updateNutricionista(
        UpdateContextoPacienteRequest $request,
        ContextoPaciente $contextoPaciente
    ): RedirectResponse {
        $contextoPaciente->load('paciente');

        $this->accessService->asegurarAccesoAPaciente($contextoPaciente->paciente);

        $contextoPaciente->update([
            'des_cxp' => $request->input('des_cxp'),
            'fec_cxp' => $request->input('fec_cxp'),
            'tip_cxp' => $request->input('tip_cxp'),
            'pri_cxp' => $request->input('pri_cxp', ContextoPaciente::PRIORIDAD_MEDIA),
            'est_cxp' => $request->input('est_cxp', ContextoPaciente::ESTADO_ACTIVO),
        ]);

        return redirect()
            ->route('nutricionista.contextos-paciente.historial', $contextoPaciente->cod_pacientes_cxp)
            ->with('success', 'Contexto clínico actualizado correctamente.');
    }

    public function inactivarNutricionista(ContextoPaciente $contextoPaciente): RedirectResponse
    {
        $contextoPaciente->load('paciente');

        $this->accessService->asegurarAccesoAPaciente($contextoPaciente->paciente);

        $contextoPaciente->inactivar();

        return redirect()
            ->route('nutricionista.contextos-paciente.historial', $contextoPaciente->cod_pacientes_cxp)
            ->with('success', 'Contexto clínico inactivado correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers privados
    |--------------------------------------------------------------------------
    */

    private function renderHistorial(Paciente $paciente, string $modo): Response
    {
        $paciente->load('usuario');

        $contextos = $paciente->contextosPaciente()
            ->recientes()
            ->get()
            ->map(fn (ContextoPaciente $contexto) => $this->mapContextoBasico($contexto))
            ->values();

        return Inertia::render('ContextosPaciente/Historial', [
            'paciente' => $this->mapPacienteBasico($paciente),
            'contextos' => $contextos,
            'modo' => $modo,
            'estados' => ContextoPaciente::ESTADOS,
            'tipos' => ContextoPaciente::TIPOS,
            'crearUrl' => $modo === 'nutricionista'
                ? route('nutricionista.contextos-paciente.create-paciente', $paciente->getKey())
                : route('contextos-paciente.create', ['paciente' => $paciente->getKey()]),
            'volverUrl' => $modo === 'nutricionista'
                ? route('nutricionista.pacientes.show', $paciente->getKey())
                : route('contextos-paciente.index'),
        ]);
    }

    private function pacientesPermitidosQuery(): Builder
    {
        if ($this->esNutricionista()) {
            return $this->accessService->pacientesAsignadosQuery();
        }

        return Paciente::query()
            ->where('est_pac', 'activo')
            ->whereHas('usuario', fn ($query) => $query->where('est_usu', 'activo'));
    }

    private function esNutricionista(): bool
    {
        return $this->rolActivo() === 'Nutricionista';
    }

    private function rolActivo(): string
    {
        return trim((string) session('nom_rol_activo', session('rol_activo')));
    }

    private function rutaHistorialContextos(): string
    {
        return $this->esNutricionista()
            ? 'nutricionista.contextos-paciente.historial'
            : 'contextos-paciente.historial';
    }

    private function mapPacienteConContextos(Paciente $paciente): array
    {
        return [
            'cod_pacientes' => $paciente->cod_pacientes,
            'sex_pac' => $paciente->sex_pac,
            'est_pac' => $paciente->est_pac,
            'usuario' => $paciente->usuario ? [
                'nom_usu' => $paciente->usuario->nom_usu,
                'ape_usu' => $paciente->usuario->ape_usu,
                'nombre_completo' => $paciente->usuario->nombre_completo,
                'cor_usu' => $paciente->usuario->cor_usu,
                'ci_usu' => $paciente->usuario->ci_usu,
                'ext_ci_usu' => $paciente->usuario->ext_ci_usu,
            ] : null,
            'contextos' => $paciente->contextosPaciente
                ->map(fn (ContextoPaciente $contexto) => $this->mapContextoBasico($contexto))
                ->values(),
        ];
    }

    private function mapPacienteBasico(Paciente $paciente): array
    {
        $paciente->loadMissing('usuario');

        return [
            'cod_pacientes' => $paciente->cod_pacientes,
            'sex_pac' => $paciente->sex_pac,
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

    private function mapContextoBasico(ContextoPaciente $contexto): array
    {
        return [
            'cod_contextos_paciente' => $contexto->cod_contextos_paciente,
            'cod_pacientes_cxp' => $contexto->cod_pacientes_cxp,
            'des_cxp' => $contexto->des_cxp,
            'fec_cxp' => optional($contexto->fec_cxp)->format('Y-m-d'),
            'tip_cxp' => $contexto->tip_cxp,
            'pri_cxp' => $contexto->pri_cxp,
            'est_cxp' => $contexto->est_cxp,
            'es_critico' => $contexto->esClinicamenteCritico(),
            'created_at' => optional($contexto->created_at)->format('Y-m-d H:i'),
        ];
    }

    private function mapContextoCompleto(ContextoPaciente $contexto): array
    {
        return [
            ...$this->mapContextoBasico($contexto),
            'paciente' => $contexto->paciente
                ? $this->mapPacienteBasico($contexto->paciente)
                : null,
        ];
    }
}