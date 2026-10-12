<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreObjetivoNutricionalRequest;
use App\Http\Requests\UpdateObjetivoNutricionalRequest;
use App\Models\ObjetivoNutricional;
use App\Models\Paciente;
use App\Services\AnalisisInteligentePacienteService;
use App\Services\NutricionistaPacienteAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\AjusteObjetivoNutricional;
use App\Models\ContextoPaciente;
use App\Models\EvaluacionInicial;
use App\Models\Nutricionista;

class ObjetivoNutricionalController extends Controller
{
    public function __construct(
        private readonly NutricionistaPacienteAccessService $accessService,
        private readonly AnalisisInteligentePacienteService $analisisInteligenteService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Flujo general: Admin / SuperAdmin / compatibilidad
    |--------------------------------------------------------------------------
    */

    public function create(Request $request): Response
    {
        $pacienteSeleccionadoId = $request->integer('paciente');

        $pacienteSeleccionado = $pacienteSeleccionadoId
            ? Paciente::query()
                ->with('usuario')
                ->where('est_pac', 'activo')
                ->whereHas('usuario', fn ($query) => $query->where('est_usu', 'activo'))
                ->find($pacienteSeleccionadoId)
            : null;

        $pacientes = $this->pacientesPermitidosQuery()
            ->with('usuario')
            ->orderBy('cod_pacientes')
            ->get()
            ->map(fn (Paciente $paciente) => $this->mapPacienteBasico($paciente))
            ->values();

        return Inertia::render('ObjetivosNutricionales/Create', [
            'pacientes' => $pacientes,
            'estados' => ObjetivoNutricional::ESTADOS,
            'pacienteSeleccionado' => $pacienteSeleccionado
                ? $this->mapPacienteBasico($pacienteSeleccionado)
                : null,
            'perfilClinico' => $pacienteSeleccionado
                ? $this->perfilClinicoPaciente($pacienteSeleccionado)
                : null,
            'analisisInteligente' => $pacienteSeleccionado
                ? $this->analisisInteligenteService->generar($pacienteSeleccionado)
                : null,
            'modo' => $this->esNutricionista() ? 'nutricionista' : 'admin',
            'action' => route('objetivos-nutricionales.store'),
            'volverUrl' => route('dashboard'),
        ]);
    }

    public function store(StoreObjetivoNutricionalRequest $request): RedirectResponse
    {
        $paciente = $request->filled('cod_pacientes_obe')
            ? Paciente::findOrFail($request->input('cod_pacientes_obe'))
            : null;

        $nutricionistaId = null;

        if ($this->esNutricionista()) {
            if (! $paciente) {
                return back()->withErrors([
                    'cod_pacientes_obe' => 'Para crear un objetivo como nutricionista debes seleccionar un paciente asignado.',
                ])->withInput();
            }

            $nutricionista = $this->accessService->asegurarAccesoAPaciente($paciente);
            $nutricionistaId = $nutricionista->getKey();
        }

        $objetivo = ObjetivoNutricional::create([
            'cod_pacientes_obe' => $paciente?->getKey(),
            'cod_nutricionistas_obe' => $nutricionistaId,
            'des_obe' => $request->input('des_obe'),
            'fec_obe' => $request->input('fec_obe'),
            'est_obe' => $request->input('est_obe', ObjetivoNutricional::ESTADO_ACTIVO),
        ]);

        $creador = auth()->user();
        $creadorName = $creador ? $creador->nom_usu : 'Sistema';
        $pacName = $paciente && $paciente->usuario ? $paciente->usuario->nombre_completo : 'Paciente #' . ($paciente?->getKey() ?? 'N/A');
        Log::info("ACTIVIDAD: El usuario '{$creadorName}' ha creado un nuevo objetivo nutricional para el paciente '{$pacName}' (Descripción: {$objetivo->des_obe}, Estado: {$objetivo->est_obe}).");

        $this->registrarAjusteAutomatico(
            objetivo: $objetivo,
            descripcion: 'Se creó el objetivo nutricional.',
            motivo: AjusteObjetivoNutricional::MOTIVO_CREACION_OBJETIVO,
            estadoAnterior: 'sin_estado',
            estadoNuevo: $objetivo->est_obe,
            descripcionAnterior: null,
            descripcionNueva: $objetivo->des_obe,
        );

        if ($paciente) {
            return redirect()
                ->route($this->rutaObjetivosPorPaciente(), $paciente->getKey())
                ->with('success', 'Objetivo nutricional registrado correctamente.');
        }

        return redirect()
            ->route('objetivos-nutricionales.show', $objetivo)
            ->with('success', 'Objetivo nutricional registrado correctamente.');
    }

    public function porPaciente(Paciente $paciente): Response
    {
        if ($this->esNutricionista()) {
            $this->accessService->asegurarAccesoAPaciente($paciente);
        }

        return $this->renderPorPaciente($paciente, $this->esNutricionista() ? 'nutricionista' : 'admin');
    }

    public function show(ObjetivoNutricional $objetivoNutricional): Response
    {
        if ($this->esNutricionista()) {
            $this->asegurarAccesoObjetivo($objetivoNutricional);
        }

        return $this->renderShow($objetivoNutricional, $this->esNutricionista() ? 'nutricionista' : 'admin');
    }

    public function edit(ObjetivoNutricional $objetivoNutricional): Response
    {
        if ($this->esNutricionista()) {
            $this->asegurarAccesoObjetivo($objetivoNutricional);
        }

        return $this->renderEdit($objetivoNutricional, $this->esNutricionista() ? 'nutricionista' : 'admin');
    }

    public function update(
        UpdateObjetivoNutricionalRequest $request,
        ObjetivoNutricional $objetivoNutricional
    ): RedirectResponse {
        if ($this->esNutricionista()) {
            $this->asegurarAccesoObjetivo($objetivoNutricional);
        }

        if (! $objetivoNutricional->puedeEditarse()) {
            return back()->withErrors([
                'objetivo' => 'No puedes editar un objetivo cumplido, cerrado o cancelado.',
            ]);
        }

        $descripcionAnterior = $objetivoNutricional->des_obe;
        $estadoAnterior = $objetivoNutricional->est_obe;

        $objetivoNutricional->update([
            'des_obe' => $request->input('des_obe'),
            'fec_obe' => $request->input('fec_obe'),
            'est_obe' => $this->esNutricionista()
                ? $objetivoNutricional->est_obe
                : $request->input('est_obe', $objetivoNutricional->est_obe),
        ]);

        $descripcionNueva = $objetivoNutricional->des_obe;
        $estadoNuevo = $objetivoNutricional->est_obe;

        if ($descripcionAnterior !== $descripcionNueva || $estadoAnterior !== $estadoNuevo) {
            $this->registrarAjusteAutomatico(
                objetivo: $objetivoNutricional,
                descripcion: 'Se actualizó la información del objetivo nutricional.',
                motivo: $estadoAnterior !== $estadoNuevo
                    ? AjusteObjetivoNutricional::MOTIVO_CAMBIO_ESTADO
                    : AjusteObjetivoNutricional::MOTIVO_CAMBIO_DESCRIPCION,
                estadoAnterior: $estadoAnterior,
                estadoNuevo: $estadoNuevo,
                descripcionAnterior: $descripcionAnterior,
                descripcionNueva: $descripcionNueva,
            );
        }

        return redirect()
            ->route($this->rutaShowObjetivo(), $objetivoNutricional->getKey())
            ->with('success', 'Objetivo nutricional actualizado correctamente.');
    }

    public function marcarCumplido(ObjetivoNutricional $objetivoNutricional): RedirectResponse
    {
        if ($this->esNutricionista()) {
            $this->asegurarAccesoObjetivo($objetivoNutricional);
        }

        $estadoAnterior = $objetivoNutricional->est_obe;

        if (! $objetivoNutricional->marcarCumplido()) {
            return back()->withErrors([
                'estado' => 'No se puede marcar como cumplido un objetivo finalizado.',
            ]);
        }

        $this->registrarAjusteAutomatico(
            objetivo: $objetivoNutricional,
            descripcion: 'El objetivo nutricional fue marcado como cumplido.',
            motivo: AjusteObjetivoNutricional::MOTIVO_CAMBIO_ESTADO,
            estadoAnterior: $estadoAnterior,
            estadoNuevo: $objetivoNutricional->est_obe,
            descripcionAnterior: $objetivoNutricional->des_obe,
            descripcionNueva: $objetivoNutricional->des_obe,
        );

        return redirect()
            ->route($this->rutaShowObjetivo(), $objetivoNutricional->getKey())
            ->with('success', 'Objetivo nutricional marcado como cumplido.');
    }

    public function pausar(ObjetivoNutricional $objetivoNutricional): RedirectResponse
    {
        if ($this->esNutricionista()) {
            $this->asegurarAccesoObjetivo($objetivoNutricional);
        }

        $estadoAnterior = $objetivoNutricional->est_obe;

        if (! $objetivoNutricional->pausar()) {
            return back()->withErrors([
                'estado' => 'Solo puedes pausar un objetivo activo.',
            ]);
        }

        $this->registrarAjusteAutomatico(
            objetivo: $objetivoNutricional,
            descripcion: 'El objetivo nutricional fue pausado.',
            motivo: AjusteObjetivoNutricional::MOTIVO_CAMBIO_ESTADO,
            estadoAnterior: $estadoAnterior,
            estadoNuevo: $objetivoNutricional->est_obe,
            descripcionAnterior: $objetivoNutricional->des_obe,
            descripcionNueva: $objetivoNutricional->des_obe,
        );

        return redirect()
            ->route($this->rutaShowObjetivo(), $objetivoNutricional->getKey())
            ->with('success', 'Objetivo nutricional pausado correctamente.');
    }

    public function cerrar(ObjetivoNutricional $objetivoNutricional): RedirectResponse
    {
        if ($this->esNutricionista()) {
            $this->asegurarAccesoObjetivo($objetivoNutricional);
        }

        $estadoAnterior = $objetivoNutricional->est_obe;

        if (! $objetivoNutricional->cerrar()) {
            return back()->withErrors([
                'estado' => 'No se puede cerrar un objetivo finalizado.',
            ]);
        }

        $this->registrarAjusteAutomatico(
            objetivo: $objetivoNutricional,
            descripcion: 'El objetivo nutricional fue cerrado.',
            motivo: AjusteObjetivoNutricional::MOTIVO_CAMBIO_ESTADO,
            estadoAnterior: $estadoAnterior,
            estadoNuevo: $objetivoNutricional->est_obe,
            descripcionAnterior: $objetivoNutricional->des_obe,
            descripcionNueva: $objetivoNutricional->des_obe,
        );

        return redirect()
            ->route($this->rutaShowObjetivo(), $objetivoNutricional->getKey())
            ->with('success', 'Objetivo nutricional cerrado correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | Flujo clínico del nutricionista
    |--------------------------------------------------------------------------
    */

    public function porPacienteNutricionista(Paciente $paciente): Response
    {
        $this->accessService->asegurarAccesoAPaciente($paciente);

        return $this->renderPorPaciente($paciente, 'nutricionista');
    }

    public function createPaciente(Paciente $paciente): Response
    {
        $this->accessService->asegurarAccesoAPaciente($paciente);

        $paciente->load('usuario');

        $perfilClinico = $this->perfilClinicoPaciente($paciente);

        return Inertia::render('ObjetivosNutricionales/Create', [
            'pacientes' => [
                $this->mapPacienteBasico($paciente),
            ],
            'estados' => ObjetivoNutricional::ESTADOS,
            'pacienteSeleccionado' => $this->mapPacienteBasico($paciente),
            'perfilClinico' => $perfilClinico,
            'analisisInteligente' => $this->analisisInteligenteService->generar($paciente),
            'modo' => 'nutricionista',
            'action' => route('nutricionista.objetivos-nutricionales.store-paciente', $paciente->getKey()),
            'volverUrl' => route('nutricionista.pacientes.show', $paciente->getKey()),
        ]);
    }

    public function storePaciente(
        StoreObjetivoNutricionalRequest $request,
        Paciente $paciente
    ): RedirectResponse {
        $nutricionista = $this->accessService->asegurarAccesoAPaciente($paciente);

        $objetivo = ObjetivoNutricional::create([
            'cod_pacientes_obe' => $paciente->getKey(),
            'cod_nutricionistas_obe' => $nutricionista->getKey(),
            'des_obe' => $request->input('des_obe'),
            'fec_obe' => $request->input('fec_obe'),
            'est_obe' => ObjetivoNutricional::ESTADO_ACTIVO,
        ]);

        $creador = auth()->user();
        $creadorName = $creador ? $creador->nom_usu : 'Sistema';
        $pacName = $paciente->usuario ? $paciente->usuario->nombre_completo : 'Paciente #' . $paciente->getKey();
        Log::info("ACTIVIDAD: El nutricionista '{$creadorName}' ha creado un objetivo nutricional para el paciente '{$pacName}' (Descripción: {$objetivo->des_obe}, Estado: {$objetivo->est_obe}).");

        $this->registrarAjusteAutomatico(
            objetivo: $objetivo,
            descripcion: 'El nutricionista creó el objetivo nutricional.',
            motivo: AjusteObjetivoNutricional::MOTIVO_CREACION_OBJETIVO,
            estadoAnterior: 'sin_estado',
            estadoNuevo: $objetivo->est_obe,
            descripcionAnterior: null,
            descripcionNueva: $objetivo->des_obe,
        );

        return redirect()
            ->route('nutricionista.objetivos-nutricionales.show', $objetivo->getKey())
            ->with('success', 'Objetivo nutricional registrado correctamente.');
    }

    public function showNutricionista(ObjetivoNutricional $objetivoNutricional): Response
    {
        $this->asegurarAccesoObjetivo($objetivoNutricional);

        return $this->renderShow($objetivoNutricional, 'nutricionista');
    }

    public function editNutricionista(ObjetivoNutricional $objetivoNutricional): Response
    {
        $this->asegurarAccesoObjetivo($objetivoNutricional);

        return $this->renderEdit($objetivoNutricional, 'nutricionista');
    }

    public function updateNutricionista(
        UpdateObjetivoNutricionalRequest $request,
        ObjetivoNutricional $objetivoNutricional
    ): RedirectResponse {
        $this->asegurarAccesoObjetivo($objetivoNutricional);

        if (! $objetivoNutricional->puedeEditarse()) {
            return back()->withErrors([
                'objetivo' => 'No puedes editar un objetivo cumplido, cerrado o cancelado.',
            ]);
        }

        $descripcionAnterior = $objetivoNutricional->des_obe;
        $estadoAnterior = $objetivoNutricional->est_obe;

        $objetivoNutricional->update([
            'des_obe' => $request->input('des_obe'),
            'fec_obe' => $request->input('fec_obe'),
            'est_obe' => $objetivoNutricional->est_obe,
        ]);

        $descripcionNueva = $objetivoNutricional->des_obe;
        $estadoNuevo = $objetivoNutricional->est_obe;

        if ($descripcionAnterior !== $descripcionNueva || $estadoAnterior !== $estadoNuevo) {
            $this->registrarAjusteAutomatico(
                objetivo: $objetivoNutricional,
                descripcion: 'El nutricionista actualizó la información del objetivo nutricional.',
                motivo: $estadoAnterior !== $estadoNuevo
                    ? AjusteObjetivoNutricional::MOTIVO_CAMBIO_ESTADO
                    : AjusteObjetivoNutricional::MOTIVO_CAMBIO_DESCRIPCION,
                estadoAnterior: $estadoAnterior,
                estadoNuevo: $estadoNuevo,
                descripcionAnterior: $descripcionAnterior,
                descripcionNueva: $descripcionNueva,
            );
        }

        return redirect()
            ->route('nutricionista.objetivos-nutricionales.show', $objetivoNutricional->getKey())
            ->with('success', 'Objetivo nutricional actualizado correctamente.');
    }

    public function marcarCumplidoNutricionista(ObjetivoNutricional $objetivoNutricional): RedirectResponse
    {
        $this->asegurarAccesoObjetivo($objetivoNutricional);

        $estadoAnterior = $objetivoNutricional->est_obe;

        if (! $objetivoNutricional->marcarCumplido()) {
            return back()->withErrors([
                'estado' => 'No se puede marcar como cumplido un objetivo finalizado.',
            ]);
        }

        $this->registrarAjusteAutomatico(
            objetivo: $objetivoNutricional,
            descripcion: 'El nutricionista marcó el objetivo como cumplido.',
            motivo: AjusteObjetivoNutricional::MOTIVO_CAMBIO_ESTADO,
            estadoAnterior: $estadoAnterior,
            estadoNuevo: $objetivoNutricional->est_obe,
            descripcionAnterior: $objetivoNutricional->des_obe,
            descripcionNueva: $objetivoNutricional->des_obe,
        );

        return redirect()
            ->route('nutricionista.objetivos-nutricionales.show', $objetivoNutricional->getKey())
            ->with('success', 'Objetivo nutricional marcado como cumplido.');
    }

    public function pausarNutricionista(ObjetivoNutricional $objetivoNutricional): RedirectResponse
    {
        $this->asegurarAccesoObjetivo($objetivoNutricional);

        $estadoAnterior = $objetivoNutricional->est_obe;

        if (! $objetivoNutricional->pausar()) {
            return back()->withErrors([
                'estado' => 'Solo puedes pausar un objetivo activo.',
            ]);
        }

        $this->registrarAjusteAutomatico(
            objetivo: $objetivoNutricional,
            descripcion: 'El nutricionista pausó el objetivo nutricional.',
            motivo: AjusteObjetivoNutricional::MOTIVO_CAMBIO_ESTADO,
            estadoAnterior: $estadoAnterior,
            estadoNuevo: $objetivoNutricional->est_obe,
            descripcionAnterior: $objetivoNutricional->des_obe,
            descripcionNueva: $objetivoNutricional->des_obe,
        );

        return redirect()
            ->route('nutricionista.objetivos-nutricionales.show', $objetivoNutricional->getKey())
            ->with('success', 'Objetivo nutricional pausado correctamente.');
    }

    public function reactivarNutricionista(ObjetivoNutricional $objetivoNutricional): RedirectResponse
    {
        $this->asegurarAccesoObjetivo($objetivoNutricional);

        $estadoAnterior = $objetivoNutricional->est_obe;

        if (! $objetivoNutricional->reactivar()) {
            return back()->withErrors([
                'estado' => 'Solo puedes reactivar un objetivo pausado.',
            ]);
        }

        $this->registrarAjusteAutomatico(
            objetivo: $objetivoNutricional,
            descripcion: 'El nutricionista reactivó el objetivo nutricional.',
            motivo: AjusteObjetivoNutricional::MOTIVO_CAMBIO_ESTADO,
            estadoAnterior: $estadoAnterior,
            estadoNuevo: $objetivoNutricional->est_obe,
            descripcionAnterior: $objetivoNutricional->des_obe,
            descripcionNueva: $objetivoNutricional->des_obe,
        );

        return redirect()
            ->route('nutricionista.objetivos-nutricionales.show', $objetivoNutricional->getKey())
            ->with('success', 'Objetivo nutricional reactivado correctamente.');
    }

    public function cerrarNutricionista(ObjetivoNutricional $objetivoNutricional): RedirectResponse
    {
        $this->asegurarAccesoObjetivo($objetivoNutricional);

        $estadoAnterior = $objetivoNutricional->est_obe;

        if (! $objetivoNutricional->cerrar()) {
            return back()->withErrors([
                'estado' => 'No se puede cerrar un objetivo finalizado.',
            ]);
        }

        $this->registrarAjusteAutomatico(
            objetivo: $objetivoNutricional,
            descripcion: 'El nutricionista cerró el objetivo nutricional.',
            motivo: AjusteObjetivoNutricional::MOTIVO_CAMBIO_ESTADO,
            estadoAnterior: $estadoAnterior,
            estadoNuevo: $objetivoNutricional->est_obe,
            descripcionAnterior: $objetivoNutricional->des_obe,
            descripcionNueva: $objetivoNutricional->des_obe,
        );

        return redirect()
            ->route('nutricionista.objetivos-nutricionales.show', $objetivoNutricional->getKey())
            ->with('success', 'Objetivo nutricional cerrado correctamente.');
    }

    public function cancelarNutricionista(ObjetivoNutricional $objetivoNutricional): RedirectResponse
    {
        $this->asegurarAccesoObjetivo($objetivoNutricional);

        $estadoAnterior = $objetivoNutricional->est_obe;

        if (! $objetivoNutricional->cancelar()) {
            return back()->withErrors([
                'estado' => 'No se puede cancelar un objetivo finalizado.',
            ]);
        }

        $this->registrarAjusteAutomatico(
            objetivo: $objetivoNutricional,
            descripcion: 'El nutricionista canceló el objetivo nutricional.',
            motivo: AjusteObjetivoNutricional::MOTIVO_CAMBIO_ESTADO,
            estadoAnterior: $estadoAnterior,
            estadoNuevo: $objetivoNutricional->est_obe,
            descripcionAnterior: $objetivoNutricional->des_obe,
            descripcionNueva: $objetivoNutricional->des_obe,
        );

        return redirect()
            ->route('nutricionista.objetivos-nutricionales.show', $objetivoNutricional->getKey())
            ->with('success', 'Objetivo nutricional cancelado correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers privados
    |--------------------------------------------------------------------------
    */

    private function renderPorPaciente(Paciente $paciente, string $modo): Response
    {
        $paciente->load('usuario');

        $objetivos = $paciente->objetivosNutricionales()
            ->with('nutricionista.usuario')
            ->latest('fec_obe')
            ->latest('cod_objetivos_nutricionales')
            ->get()
            ->map(fn (ObjetivoNutricional $objetivo) => $this->mapObjetivoResumen($objetivo))
            ->values();

        return Inertia::render('ObjetivosNutricionales/PorPaciente', [
            'paciente' => $this->mapPacienteBasico($paciente),
            'objetivos' => $objetivos,
            'modo' => $modo,
            'crearUrl' => $modo === 'nutricionista'
                ? route('nutricionista.objetivos-nutricionales.create-paciente', $paciente->getKey())
                : route('objetivos-nutricionales.create', ['paciente' => $paciente->getKey()]),
            'volverUrl' => $modo === 'nutricionista'
                ? route('nutricionista.pacientes.show', $paciente->getKey())
                : route('dashboard'),
        ]);
    }

    private function renderShow(ObjetivoNutricional $objetivo, string $modo): Response
    {
        $objetivo->load([
            'paciente.usuario',
            'nutricionista.usuario',
            'ajustes.nutricionista.usuario',
        ]);

        return Inertia::render('ObjetivosNutricionales/Show', [
            'objetivo' => $this->mapObjetivoCompleto($objetivo),
            'modo' => $modo,

            'editarUrl' => $modo === 'nutricionista'
                ? route('nutricionista.objetivos-nutricionales.edit', $objetivo->getKey())
                : route('objetivos-nutricionales.edit', $objetivo->getKey()),

            'volverUrl' => $modo === 'nutricionista' && $objetivo->paciente
                ? route('nutricionista.objetivos-nutricionales.paciente', $objetivo->paciente->getKey())
                : ($objetivo->paciente
                    ? route('objetivos-nutricionales.paciente', $objetivo->paciente->getKey())
                    : route('dashboard')),

            'ajustesUrl' => $modo === 'nutricionista'
                ? route('nutricionista.ajustes-objetivos-nutricionales.historial', $objetivo->getKey())
                : route('ajustes-objetivos-nutricionales.historial', $objetivo->getKey()),

            'ajusteCrearUrl' => $modo === 'nutricionista'
                ? route('nutricionista.ajustes-objetivos-nutricionales.create', $objetivo->getKey())
                : route('ajustes-objetivos-nutricionales.create', [
                    'objetivo' => $objetivo->getKey(),
                ]),
            'rutasEstado' => [
                'cumplido' => $modo === 'nutricionista'
                    ? route('nutricionista.objetivos-nutricionales.cumplido', $objetivo->getKey())
                    : route('objetivos-nutricionales.cumplido', $objetivo->getKey()),
                'pausar' => $modo === 'nutricionista'
                    ? route('nutricionista.objetivos-nutricionales.pausar', $objetivo->getKey())
                    : route('objetivos-nutricionales.pausar', $objetivo->getKey()),
                'reactivar' => $modo === 'nutricionista'
                    ? route('nutricionista.objetivos-nutricionales.reactivar', $objetivo->getKey())
                    : null,
                'cerrar' => $modo === 'nutricionista'
                    ? route('nutricionista.objetivos-nutricionales.cerrar', $objetivo->getKey())
                    : route('objetivos-nutricionales.cerrar', $objetivo->getKey()),
                'cancelar' => $modo === 'nutricionista'
                    ? route('nutricionista.objetivos-nutricionales.cancelar', $objetivo->getKey())
                    : null,
            ],
        ]);
    }

    private function renderEdit(ObjetivoNutricional $objetivo, string $modo): Response
    {
        $objetivo->load([
            'paciente.usuario',
            'nutricionista.usuario',
        ]);

        return Inertia::render('ObjetivosNutricionales/Edit', [
            'objetivo' => $this->mapObjetivoCompleto($objetivo),
            'estados' => $modo === 'nutricionista'
                ? [
                    ObjetivoNutricional::ESTADO_ACTIVO,
                    ObjetivoNutricional::ESTADO_PAUSADO,
                ]
                : ObjetivoNutricional::ESTADOS,
            'puedeCambiarEstadoDesdeEdicion' => $modo !== 'nutricionista',
            'modo' => $modo,
            'action' => $modo === 'nutricionista'
                ? route('nutricionista.objetivos-nutricionales.update', $objetivo->getKey())
                : route('objetivos-nutricionales.update', $objetivo->getKey()),
            'volverUrl' => $modo === 'nutricionista'
                ? route('nutricionista.objetivos-nutricionales.show', $objetivo->getKey())
                : route('objetivos-nutricionales.show', $objetivo->getKey()),
        ]);
    }

    private function asegurarAccesoObjetivo(ObjetivoNutricional $objetivo): void
    {
        $objetivo->loadMissing('paciente');

        abort_if(! $objetivo->paciente, 403, 'Este objetivo no está asociado a un paciente.');

        $this->accessService->asegurarAccesoAPaciente($objetivo->paciente);
    }

    private function pacientesPermitidosQuery()
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

    private function rutaObjetivosPorPaciente(): string
    {
        return $this->esNutricionista()
            ? 'nutricionista.objetivos-nutricionales.paciente'
            : 'objetivos-nutricionales.paciente';
    }

    private function rutaShowObjetivo(): string
    {
        return $this->esNutricionista()
            ? 'nutricionista.objetivos-nutricionales.show'
            : 'objetivos-nutricionales.show';
    }

    private function mapPacienteBasico(Paciente $paciente): array
    {
        $paciente->loadMissing('usuario');

        return [
            'cod_pacientes' => $paciente->cod_pacientes,
            'usuario' => $paciente->usuario ? [
                'nombre_completo' => $paciente->usuario->nombre_completo,
                'cor_usu' => $paciente->usuario->cor_usu,
                'ci_usu' => $paciente->usuario->ci_usu,
                'ext_ci_usu' => $paciente->usuario->ext_ci_usu,
            ] : null,
        ];
    }

    private function mapObjetivoResumen(ObjetivoNutricional $objetivo): array
    {
        return [
            'cod_objetivos_nutricionales' => $objetivo->cod_objetivos_nutricionales,
            'cod_pacientes_obe' => $objetivo->cod_pacientes_obe,
            'cod_nutricionistas_obe' => $objetivo->cod_nutricionistas_obe,
            'des_obe' => $objetivo->des_obe,
            'fec_obe' => optional($objetivo->fec_obe)->format('Y-m-d'),
            'est_obe' => $objetivo->est_obe,
            'nutricionista' => $objetivo->nutricionista
                ? $this->mapNutricionistaBasico($objetivo->nutricionista)
                : null,
        ];
    }

    private function mapObjetivoCompleto(ObjetivoNutricional $objetivo): array
    {
        return [
            'cod_objetivos_nutricionales' => $objetivo->cod_objetivos_nutricionales,
            'cod_pacientes_obe' => $objetivo->cod_pacientes_obe,
            'cod_nutricionistas_obe' => $objetivo->cod_nutricionistas_obe,
            'des_obe' => $objetivo->des_obe,
            'fec_obe' => optional($objetivo->fec_obe)->format('Y-m-d'),
            'est_obe' => $objetivo->est_obe,
            'paciente' => $objetivo->paciente
                ? $this->mapPacienteBasico($objetivo->paciente)
                : null,
            'nutricionista' => $objetivo->nutricionista
                ? $this->mapNutricionistaBasico($objetivo->nutricionista)
                : null,
            'ajustes' => $objetivo->ajustes
                ? $objetivo->ajustes->map(fn ($ajuste) => [
                    'cod_ajustes_objetivos_nutricionales' => $ajuste->cod_ajustes_objetivos_nutricionales,
                    'fec_ajn' => optional($ajuste->fec_ajn)->format('Y-m-d'),
                    'des_ajn' => $ajuste->des_ajn,
                    'motivo_ajn' => $ajuste->motivo_ajn,
                    'estado_anterior_ajn' => $ajuste->estado_anterior_ajn,
                    'estado_nuevo_ajn' => $ajuste->estado_nuevo_ajn,
                    'nutricionista' => $ajuste->nutricionista ? [
                        'usuario' => $ajuste->nutricionista->usuario ? [
                            'nombre_completo' => $ajuste->nutricionista->usuario->nombre_completo,
                        ] : null,
                    ] : null,
                ])->values()
                : [],
        ];
    }

    private function mapNutricionistaBasico($nutricionista): array
    {
        $nutricionista->loadMissing('usuario');

        return [
            'cod_nutricionistas' => $nutricionista->cod_nutricionistas,
            'esp_nut' => $nutricionista->esp_nut,
            'usuario' => $nutricionista->usuario ? [
                'nombre_completo' => $nutricionista->usuario->nombre_completo,
                'cor_usu' => $nutricionista->usuario->cor_usu,
                'ci_usu' => $nutricionista->usuario->ci_usu,
                'ext_ci_usu' => $nutricionista->usuario->ext_ci_usu,
            ] : null,
        ];
    }

    private function registrarAjusteAutomatico(
        ObjetivoNutricional $objetivo,
        string $descripcion,
        string $motivo,
        ?string $estadoAnterior = null,
        ?string $estadoNuevo = null,
        ?string $descripcionAnterior = null,
        ?string $descripcionNueva = null
    ): void {
        $nutricionista = $this->nutricionistaResponsableParaAjuste($objetivo);

        if (! $nutricionista) {
            return;
        }

        AjusteObjetivoNutricional::registrarCambio(
            objetivo: $objetivo,
            nutricionista: $nutricionista,
            descripcion: $descripcion,
            motivo: $motivo,
            estadoAnterior: $estadoAnterior,
            estadoNuevo: $estadoNuevo,
            descripcionAnterior: $descripcionAnterior,
            descripcionNueva: $descripcionNueva,
        );
    }

    private function nutricionistaResponsableParaAjuste(ObjetivoNutricional $objetivo): ?Nutricionista
    {
        if ($this->esNutricionista()) {
            return $this->accessService->obtenerNutricionistaAutenticadoOrFail();
        }

        $objetivo->loadMissing('nutricionista');

        return $objetivo->nutricionista;
    }

    private function perfilClinicoPaciente(Paciente $paciente): array
    {
        $evaluacionInicial = EvaluacionInicial::query()
            ->where('cod_pacientes_evi', $paciente->getKey())
            ->vigentes()
            ->latest('cod_evaluaciones_iniciales')
            ->first();

        $contextosActivos = $paciente->contextosPaciente()
            ->activos()
            ->orderByRaw("\n                CASE pri_cxp\n                    WHEN 'alta' THEN 1\n                    WHEN 'media' THEN 2\n                    WHEN 'baja' THEN 3\n                    ELSE 4\n                END\n            ")
            ->orderByDesc('fec_cxp')
            ->get();

        $contextosCriticos = $contextosActivos
            ->filter(fn ($contexto) => $contexto->esClinicamenteCritico());

        $alertas = [];

        if (! $evaluacionInicial) {
            $alertas[] = 'El paciente aún no tiene evaluación inicial vigente. El objetivo puede registrarse, pero conviene completar ese checkpoint.';
        }

        if ($evaluacionInicial?->restricciones_evi) {
            $alertas[] = 'Revisar restricciones alimentarias registradas en la evaluación inicial.';
        }

        if ($paciente->ale_pac) {
            $alertas[] = 'El paciente tiene alergias registradas en su ficha.';
        }

        if ($contextosActivos->isEmpty()) {
            $alertas[] = 'El paciente aún no tiene contextos clínicos activos registrados.';
        }

        if ($contextosCriticos->isNotEmpty()) {
            $alertas[] = 'Este paciente tiene contextos clínicos críticos activos. Revísalos antes de definir el objetivo nutricional.';
        }

        if ($contextosCriticos->contains(fn ($contexto) => $contexto->tip_cxp === ContextoPaciente::TIPO_ALERGIA)) {
            $alertas[] = 'Existe una alergia activa de alta prioridad. Evita redactar objetivos que sugieran alimentos asociados al riesgo.';
        }

        if ($contextosCriticos->contains(fn ($contexto) => $contexto->tip_cxp === ContextoPaciente::TIPO_RESTRICCION_ALIMENTARIA)) {
            $alertas[] = 'Existe una restricción alimentaria crítica. El objetivo debe respetar esa limitación.';
        }

        if ($contextosCriticos->contains(fn ($contexto) => $contexto->tip_cxp === ContextoPaciente::TIPO_PATOLOGIA)) {
            $alertas[] = 'Existe una patología crítica activa. El objetivo debe formularse considerando esa condición clínica.';
        }

        $sugerencias = [];

        if ($contextosCriticos->isNotEmpty()) {
            $sugerencias[] = [
                'titulo' => 'Objetivo considerando contextos críticos',
                'texto' => 'Adaptar progresivamente el patrón alimentario del paciente durante las próximas 4 semanas, considerando los contextos clínicos críticos activos, restricciones registradas y seguimiento nutricional profesional.',
            ];
        }

        if ($contextosCriticos->contains(fn ($contexto) => $contexto->tip_cxp === ContextoPaciente::TIPO_ALERGIA)) {
            $sugerencias[] = [
                'titulo' => 'Objetivo considerando alergias',
                'texto' => 'Mejorar la selección de alimentos seguros durante las próximas 4 semanas, evitando alimentos asociados a alergias registradas y reforzando alternativas nutricionalmente adecuadas.',
            ];
        }

        if ($contextosCriticos->contains(fn ($contexto) => $contexto->tip_cxp === ContextoPaciente::TIPO_RESTRICCION_ALIMENTARIA)) {
            $sugerencias[] = [
                'titulo' => 'Objetivo considerando restricciones',
                'texto' => 'Ajustar el consumo alimentario durante las próximas 4 semanas respetando las restricciones alimentarias activas y manteniendo una alimentación equilibrada según el seguimiento nutricional.',
            ];
        }

        return [
            'evaluacion_inicial' => $evaluacionInicial ? [
                'fec_evi' => optional($evaluacionInicial->fec_evi)->format('Y-m-d'),
                'peso_evi' => $evaluacionInicial->peso_evi,
                'estatura_evi' => $evaluacionInicial->estatura_evi,
                'imc_evi' => $evaluacionInicial->imc_evi,
                'objetivo_inicial_evi' => $evaluacionInicial->objetivo_inicial_evi,
                'restricciones_evi' => $evaluacionInicial->restricciones_evi,
                'contexto_general_evi' => $evaluacionInicial->contexto_general_evi,
                'est_evi' => $evaluacionInicial->est_evi,
            ] : null,

            'paciente' => [
                'ale_pac' => $paciente->ale_pac,
                'tip_san_pac' => $paciente->tip_san_pac,
                'pes_pac' => $paciente->pes_pac,
                'estatura_pac' => $paciente->estatura_pac,
            ],

            'contextos_activos' => $contextosActivos->map(fn (ContextoPaciente $contexto) => [
                'cod_contextos_paciente' => $contexto->cod_contextos_paciente,
                'tip_cxp' => $contexto->tip_cxp,
                'pri_cxp' => $contexto->pri_cxp,
                'des_cxp' => $contexto->des_cxp,
                'fec_cxp' => optional($contexto->fec_cxp)->format('Y-m-d'),
                'est_cxp' => $contexto->est_cxp,
                'es_critico' => $contexto->esClinicamenteCritico(),
            ])->values(),

            'contextos_criticos' => $contextosCriticos
                ->map(fn (ContextoPaciente $contexto) => [
                    'cod_contextos_paciente' => $contexto->cod_contextos_paciente,
                    'tip_cxp' => $contexto->tip_cxp,
                    'pri_cxp' => $contexto->pri_cxp,
                    'des_cxp' => $contexto->des_cxp,
                    'fec_cxp' => optional($contexto->fec_cxp)->format('Y-m-d'),
                    'est_cxp' => $contexto->est_cxp,
                    'es_critico' => true,
                ])
                ->values(),

            'alertas' => array_values(array_unique(array_merge($alertas, []))),
            'sugerencias_objetivo' => $sugerencias,
        ];
    }

}