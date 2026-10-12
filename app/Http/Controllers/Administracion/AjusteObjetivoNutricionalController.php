<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAjusteObjetivoNutricionalRequest;
use App\Models\AjusteObjetivoNutricional;
use App\Models\Nutricionista;
use App\Models\ObjetivoNutricional;
use App\Services\NutricionistaPacienteAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class AjusteObjetivoNutricionalController extends Controller
{
    public function __construct(
        private readonly NutricionistaPacienteAccessService $accessService
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        $objetivoSeleccionadoId = $request->integer('objetivo');

        $objetivoSeleccionado = $objetivoSeleccionadoId
            ? ObjetivoNutricional::query()
                ->with(['paciente.usuario', 'nutricionista.usuario'])
                ->find($objetivoSeleccionadoId)
            : null;

        if ($this->esNutricionista() && $objetivoSeleccionado) {
            $this->asegurarAccesoObjetivo($objetivoSeleccionado);
        }

        $objetivos = $this->objetivosPermitidosQuery()
            ->with(['paciente.usuario', 'nutricionista.usuario'])
            ->orderByDesc('cod_objetivos_nutricionales')
            ->get()
            ->map(fn (ObjetivoNutricional $objetivo) => $this->mapObjetivoBasico($objetivo))
            ->values();

        $nutricionistaSeleccionado = '';

        if ($this->esNutricionista()) {
            $nutricionista = $this->accessService->obtenerNutricionistaAutenticadoOrFail();
            $nutricionistaSeleccionado = $nutricionista->getKey();
        } elseif ($objetivoSeleccionado?->cod_nutricionistas_obe) {
            $nutricionistaSeleccionado = $objetivoSeleccionado->cod_nutricionistas_obe;
        }

        $nutricionistas = $this->nutricionistasPermitidos();

        return Inertia::render('AjustesObjetivosNutricionales/Create', [
            'objetivos' => $objetivos,
            'nutricionistas' => $nutricionistas,
            'objetivoSeleccionado' => $objetivoSeleccionado?->getKey() ?: '',
            'objetivo' => $objetivoSeleccionado
                ? $this->mapObjetivoBasico($objetivoSeleccionado)
                : null,
            'nutricionistaSeleccionado' => $nutricionistaSeleccionado ?: '',
            'motivos' => AjusteObjetivoNutricional::MOTIVOS_MANUALES,
            'modo' => $this->esNutricionista() ? 'nutricionista' : 'admin',
            'action' => route('ajustes-objetivos-nutricionales.store'),
            'volverUrl' => $objetivoSeleccionado
                ? route('ajustes-objetivos-nutricionales.historial', $objetivoSeleccionado->getKey())
                : route('dashboard'),
        ]);
    }

    public function store(StoreAjusteObjetivoNutricionalRequest $request): RedirectResponse
    {
        $objetivo = ObjetivoNutricional::query()
            ->with(['paciente', 'nutricionista'])
            ->findOrFail($request->input('cod_objetivos_nutricionales_ajn'));

        if ($this->esNutricionista()) {
            $nutricionista = $this->accessService->asegurarAccesoAPaciente($objetivo->paciente);
        } else {
            $nutricionista = $this->nutricionistaParaAjuste($request, $objetivo);
        }

        AjusteObjetivoNutricional::create([
            'cod_objetivos_nutricionales_ajn' => $objetivo->getKey(),
            'cod_nutricionistas_ajn' => $nutricionista?->getKey(),
            'fec_ajn' => $request->input('fec_ajn'),
            'des_ajn' => $request->input('des_ajn'),
            'motivo_ajn' => $request->input(
                'motivo_ajn',
                AjusteObjetivoNutricional::MOTIVO_AJUSTE_MANUAL
            ),
            'estado_anterior_ajn' => $objetivo->est_obe,
            'estado_nuevo_ajn' => $objetivo->est_obe,
            'descripcion_anterior_ajn' => $objetivo->des_obe,
            'descripcion_nueva_ajn' => $objetivo->des_obe,
        ]);

        $editor = auth()->user();
        $editorName = $editor ? $editor->nom_usu : 'Sistema';
        $pacName = $objetivo->paciente && $objetivo->paciente->usuario ? $objetivo->paciente->usuario->nombre_completo : 'Paciente #' . $objetivo->cod_pacientes_obe;
        Log::info("ACTIVIDAD: El usuario '{$editorName}' ha ajustado el objetivo nutricional del paciente '{$pacName}' (Motivo: " . $request->input('motivo_ajn') . ", Descripción: " . $request->input('des_ajn') . ").");

        return redirect()
            ->route('ajustes-objetivos-nutricionales.historial', $objetivo->getKey())
            ->with('success', 'Ajuste del objetivo registrado correctamente.');
    }

    public function historial(ObjetivoNutricional $objetivoNutricional): Response
    {
        if ($this->esNutricionista()) {
            $this->asegurarAccesoObjetivo($objetivoNutricional);
        }

        $objetivoNutricional->load([
            'paciente.usuario',
            'nutricionista.usuario',
        ]);

        $ajustes = $objetivoNutricional->ajustes()
            ->with('nutricionista.usuario')
            ->recientes()
            ->get()
            ->map(fn (AjusteObjetivoNutricional $ajuste) => $this->mapAjusteBasico($ajuste))
            ->values();

        return Inertia::render('AjustesObjetivosNutricionales/Historial', [
            'objetivo' => $this->mapObjetivoCompleto($objetivoNutricional),
            'ajustes' => $ajustes,
            'modo' => $this->esNutricionista() ? 'nutricionista' : 'admin',
            'crearUrl' => route('ajustes-objetivos-nutricionales.create', [
                'objetivo' => $objetivoNutricional->getKey(),
            ]),
            'volverUrl' => $this->volverObjetivoUrl($objetivoNutricional),
        ]);
    }

    public function show(AjusteObjetivoNutricional $ajusteObjetivoNutricional): Response
    {
        $ajusteObjetivoNutricional->load([
            'objetivoNutricional.paciente.usuario',
            'objetivoNutricional.nutricionista.usuario',
            'nutricionista.usuario',
        ]);

        if ($this->esNutricionista()) {
            $this->asegurarAccesoObjetivo($ajusteObjetivoNutricional->objetivoNutricional);
        }

        return Inertia::render('AjustesObjetivosNutricionales/Show', [
            'ajuste' => $this->mapAjusteCompleto($ajusteObjetivoNutricional),
            'modo' => $this->esNutricionista() ? 'nutricionista' : 'admin',
            'volverUrl' => route(
                'ajustes-objetivos-nutricionales.historial',
                $ajusteObjetivoNutricional->cod_objetivos_nutricionales_ajn
            ),
            'objetivoUrl' => $this->objetivoShowUrl($ajusteObjetivoNutricional->objetivoNutricional),
        ]);
    }

    public function historialNutricionista(ObjetivoNutricional $objetivoNutricional): Response
    {
        $this->asegurarAccesoObjetivo($objetivoNutricional);

        $objetivoNutricional->load([
            'paciente.usuario',
            'nutricionista.usuario',
        ]);

        $ajustes = $objetivoNutricional->ajustes()
            ->with('nutricionista.usuario')
            ->recientes()
            ->get()
            ->map(fn (AjusteObjetivoNutricional $ajuste) => $this->mapAjusteBasico($ajuste))
            ->values();

        return Inertia::render('AjustesObjetivosNutricionales/Historial', [
            'objetivo' => $this->mapObjetivoCompleto($objetivoNutricional),
            'ajustes' => $ajustes,
            'modo' => 'nutricionista',
            'crearUrl' => route('nutricionista.ajustes-objetivos-nutricionales.create', $objetivoNutricional->getKey()),
            'volverUrl' => route('nutricionista.objetivos-nutricionales.show', $objetivoNutricional->getKey()),
        ]);
    }

    public function createNutricionista(ObjetivoNutricional $objetivoNutricional): Response
    {
        $nutricionista = $this->accessService->asegurarAccesoAPaciente($objetivoNutricional->paciente);

        $objetivoNutricional->load([
            'paciente.usuario',
            'nutricionista.usuario',
        ]);

        return Inertia::render('AjustesObjetivosNutricionales/Create', [
            'objetivos' => [
                $this->mapObjetivoBasico($objetivoNutricional),
            ],
            'nutricionistas' => [
                $this->mapNutricionistaBasico($nutricionista),
            ],
            'objetivoSeleccionado' => $objetivoNutricional->getKey(),
            'objetivo' => $this->mapObjetivoBasico($objetivoNutricional),
            'nutricionistaSeleccionado' => $nutricionista->getKey(),
            'motivos' => AjusteObjetivoNutricional::MOTIVOS_MANUALES,
            'modo' => 'nutricionista',
            'action' => route('nutricionista.ajustes-objetivos-nutricionales.store', $objetivoNutricional->getKey()),
            'volverUrl' => route('nutricionista.ajustes-objetivos-nutricionales.historial', $objetivoNutricional->getKey()),
        ]);
    }

    public function storeNutricionista(
        StoreAjusteObjetivoNutricionalRequest $request,
        ObjetivoNutricional $objetivoNutricional
    ): RedirectResponse {
        $nutricionista = $this->accessService->asegurarAccesoAPaciente($objetivoNutricional->paciente);

        AjusteObjetivoNutricional::create([
            'cod_objetivos_nutricionales_ajn' => $objetivoNutricional->getKey(),
            'cod_nutricionistas_ajn' => $nutricionista->getKey(),
            'fec_ajn' => $request->input('fec_ajn'),
            'des_ajn' => $request->input('des_ajn'),
            'motivo_ajn' => $request->input(
                'motivo_ajn',
                AjusteObjetivoNutricional::MOTIVO_AJUSTE_MANUAL
            ),
            'estado_anterior_ajn' => $objetivoNutricional->est_obe,
            'estado_nuevo_ajn' => $objetivoNutricional->est_obe,
            'descripcion_anterior_ajn' => $objetivoNutricional->des_obe,
            'descripcion_nueva_ajn' => $objetivoNutricional->des_obe,
        ]);

        $editor = auth()->user();
        $editorName = $editor ? $editor->nom_usu : 'Sistema';
        $pacName = $objetivoNutricional->paciente && $objetivoNutricional->paciente->usuario ? $objetivoNutricional->paciente->usuario->nombre_completo : 'Paciente #' . $objetivoNutricional->cod_pacientes_obe;
        Log::info("ACTIVIDAD: El nutricionista '{$editorName}' ha ajustado el objetivo nutricional del paciente '{$pacName}' (Motivo: " . $request->input('motivo_ajn') . ", Descripción: " . $request->input('des_ajn') . ").");

        return redirect()
            ->route('nutricionista.ajustes-objetivos-nutricionales.historial', $objetivoNutricional->getKey())
            ->with('success', 'Ajuste del objetivo registrado correctamente.');
    }

    public function showNutricionista(AjusteObjetivoNutricional $ajusteObjetivoNutricional): Response
    {
        $ajusteObjetivoNutricional->load([
            'objetivoNutricional.paciente.usuario',
            'objetivoNutricional.nutricionista.usuario',
            'nutricionista.usuario',
        ]);

        $this->asegurarAccesoObjetivo($ajusteObjetivoNutricional->objetivoNutricional);

        return Inertia::render('AjustesObjetivosNutricionales/Show', [
            'ajuste' => $this->mapAjusteCompleto($ajusteObjetivoNutricional),
            'modo' => 'nutricionista',
            'volverUrl' => route(
                'nutricionista.ajustes-objetivos-nutricionales.historial',
                $ajusteObjetivoNutricional->cod_objetivos_nutricionales_ajn
            ),
            'objetivoUrl' => route(
                'nutricionista.objetivos-nutricionales.show',
                $ajusteObjetivoNutricional->cod_objetivos_nutricionales_ajn
            ),
        ]);
    }

    private function objetivosPermitidosQuery()
    {
        if (! $this->esNutricionista()) {
            return ObjetivoNutricional::query();
        }

        $nutricionista = $this->accessService->obtenerNutricionistaAutenticadoOrFail();

        return ObjetivoNutricional::query()
            ->whereHas('paciente.asignaciones', function ($query) use ($nutricionista) {
                $query->where('cod_nutricionistas_anp', $nutricionista->getKey())
                    ->where('est_anp', 'activa');
            });
    }

    private function nutricionistasPermitidos()
    {
        if ($this->esNutricionista()) {
            $nutricionista = $this->accessService->obtenerNutricionistaAutenticadoOrFail();

            return collect([
                $this->mapNutricionistaBasico($nutricionista),
            ]);
        }

        return Nutricionista::query()
            ->with('usuario')
            ->where('est_nut', 'activo')
            ->whereHas('usuario', fn ($query) => $query->where('est_usu', 'activo'))
            ->orderBy('cod_nutricionistas')
            ->get()
            ->map(fn (Nutricionista $nutricionista) => $this->mapNutricionistaBasico($nutricionista))
            ->values();
    }

    private function nutricionistaParaAjuste(
        StoreAjusteObjetivoNutricionalRequest $request,
        ObjetivoNutricional $objetivo
    ): ?Nutricionista {
        if ($request->filled('cod_nutricionistas_ajn')) {
            return Nutricionista::find($request->input('cod_nutricionistas_ajn'));
        }

        if ($objetivo->nutricionista) {
            return $objetivo->nutricionista;
        }

        return null;
    }

    private function asegurarAccesoObjetivo(ObjetivoNutricional $objetivo): void
    {
        $objetivo->loadMissing('paciente');

        abort_if(! $objetivo->paciente, 403, 'Este objetivo no está asociado a un paciente.');

        $this->accessService->asegurarAccesoAPaciente($objetivo->paciente);
    }

    private function esNutricionista(): bool
    {
        return $this->rolActivo() === 'Nutricionista';
    }

    private function rolActivo(): string
    {
        return trim((string) session('nom_rol_activo', session('rol_activo')));
    }

    private function objetivoShowUrl(?ObjetivoNutricional $objetivo): string
    {
        if (! $objetivo) {
            return route('dashboard');
        }

        if ($this->esNutricionista()) {
            return route('nutricionista.objetivos-nutricionales.show', $objetivo->getKey());
        }

        return route('objetivos-nutricionales.show', $objetivo->getKey());
    }

    private function volverObjetivoUrl(ObjetivoNutricional $objetivo): string
    {
        if ($this->esNutricionista()) {
            return route('nutricionista.objetivos-nutricionales.show', $objetivo->getKey());
        }

        return route('objetivos-nutricionales.show', $objetivo->getKey());
    }

    private function mapObjetivoBasico(ObjetivoNutricional $objetivo): array
    {
        $objetivo->loadMissing([
            'paciente.usuario',
            'nutricionista.usuario',
        ]);

        return [
            'cod_objetivos_nutricionales' => $objetivo->cod_objetivos_nutricionales,
            'des_obe' => $objetivo->des_obe,
            'fec_obe' => optional($objetivo->fec_obe)->format('Y-m-d'),
            'est_obe' => $objetivo->est_obe,
            'paciente' => $objetivo->paciente ? [
                'cod_pacientes' => $objetivo->paciente->cod_pacientes,
                'usuario' => $objetivo->paciente->usuario ? [
                    'nombre_completo' => $objetivo->paciente->usuario->nombre_completo,
                    'cor_usu' => $objetivo->paciente->usuario->cor_usu,
                ] : null,
            ] : null,
            'nutricionista' => $objetivo->nutricionista
                ? $this->mapNutricionistaBasico($objetivo->nutricionista)
                : null,
        ];
    }

    private function mapObjetivoCompleto(ObjetivoNutricional $objetivo): array
    {
        return [
            ...$this->mapObjetivoBasico($objetivo),
            'cod_pacientes_obe' => $objetivo->cod_pacientes_obe,
            'cod_nutricionistas_obe' => $objetivo->cod_nutricionistas_obe,
        ];
    }

    private function mapAjusteBasico(AjusteObjetivoNutricional $ajuste): array
    {
        return [
            'cod_ajustes_objetivos_nutricionales' => $ajuste->cod_ajustes_objetivos_nutricionales,
            'cod_objetivos_nutricionales_ajn' => $ajuste->cod_objetivos_nutricionales_ajn,
            'cod_nutricionistas_ajn' => $ajuste->cod_nutricionistas_ajn,
            'fec_ajn' => optional($ajuste->fec_ajn)->format('Y-m-d'),
            'des_ajn' => $ajuste->des_ajn,
            'motivo_ajn' => $ajuste->motivo_ajn,
            'estado_anterior_ajn' => $ajuste->estado_anterior_ajn,
            'estado_nuevo_ajn' => $ajuste->estado_nuevo_ajn,
            'descripcion_anterior_ajn' => $ajuste->descripcion_anterior_ajn,
            'descripcion_nueva_ajn' => $ajuste->descripcion_nueva_ajn,
            'nutricionista' => $ajuste->nutricionista
                ? $this->mapNutricionistaBasico($ajuste->nutricionista)
                : null,
        ];
    }

    private function mapAjusteCompleto(AjusteObjetivoNutricional $ajuste): array
    {
        return [
            ...$this->mapAjusteBasico($ajuste),
            'objetivo' => $ajuste->objetivoNutricional
                ? $this->mapObjetivoCompleto($ajuste->objetivoNutricional)
                : null,
        ];
    }

    private function mapNutricionistaBasico(Nutricionista $nutricionista): array
    {
        $nutricionista->loadMissing('usuario');

        return [
            'cod_nutricionistas' => $nutricionista->cod_nutricionistas,
            'esp_nut' => $nutricionista->esp_nut,
            'usuario' => $nutricionista->usuario ? [
                'nombre_completo' => $nutricionista->usuario->nombre_completo,
                'cor_usu' => $nutricionista->usuario->cor_usu,
            ] : null,
        ];
    }
}