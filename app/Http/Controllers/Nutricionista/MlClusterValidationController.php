<?php

namespace App\Http\Controllers\Nutricionista;

use App\Http\Controllers\Controller;
use App\Models\AsignacionPatronPaciente;
use App\Models\ClusterModelo;
use App\Models\ModeloMl;
use App\Models\PatronAlimenticio;
use App\Models\PrediccionMl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MlClusterValidationController extends Controller
{
    /**
     * Valida profesionalmente un cluster del modelo, asociando un patrón alimenticio y descripción profesional.
     */
    public function validarCluster(Request $request, ClusterModelo $cluster): RedirectResponse
    {
        $usuario = Auth::user();
        $nutricionista = $usuario?->nutricionista;

        $request->validate([
            'cod_patrones_alimenticios' => ['nullable', 'exists:patrones_alimenticios,cod_patrones_alimenticios'],
            'cod_patrones_alimenticios_clm' => ['nullable', 'exists:patrones_alimenticios,cod_patrones_alimenticios'],
            'nuevo_nombre_patron' => ['nullable', 'string', 'max:100'],
            'nueva_descripcion_patron' => ['nullable', 'string', 'max:500'],
            'descripcion_profesional_clm' => ['required', 'string', 'min:10', 'max:2000'],
            'estado_validacion_clm' => ['required', 'in:validado,ajustado,pendiente'],
        ]);

        DB::transaction(function () use ($request, $cluster, $nutricionista) {
            $patronId = $request->input('cod_patrones_alimenticios') ?: $request->input('cod_patrones_alimenticios_clm');

            // Si se solicitó crear un nuevo patrón de negocio al vuelo
            if (!$patronId && $request->filled('nuevo_nombre_patron')) {
                $patron = PatronAlimenticio::create([
                    'nom_pat' => $request->input('nuevo_nombre_patron'),
                    'des_pat' => $request->input('nueva_descripcion_patron') ?: $request->input('descripcion_profesional_clm'),
                ]);
                $patronId = $patron->cod_patrones_alimenticios;
            }

            // Actualizar cluster
            $cluster->update([
                'cod_patrones_alimenticios_clm' => $patronId,
                'descripcion_profesional_clm' => $request->input('descripcion_profesional_clm'),
                'estado_validacion_clm' => $request->input('estado_validacion_clm'),
                'cod_nutricionistas_validador_clm' => $nutricionista?->cod_nutricionistas,
                'fec_validacion_clm' => now(),
            ]);

            // Si ahora tiene patrón asociado, crear asignaciones_patron_paciente para predicciones existentes en este cluster
            if ($patronId) {
                $predicciones = PrediccionMl::query()
                    ->where('cod_modelos_ml_pml', $cluster->cod_modelos_ml_clm)
                    ->where('resultado_principal_pml', 'Cluster ' . $cluster->numero_cluster_clm)
                    ->get();

                foreach ($predicciones as $pred) {
                    AsignacionPatronPaciente::updateOrCreate(
                        [
                            'cod_pacientes_app' => $pred->cod_pacientes_pml,
                            'cod_predicciones_ml_app' => $pred->cod_predicciones_ml,
                        ],
                        [
                            'cod_patrones_alimenticios_app' => $patronId,
                            'fec_app' => now()->toDateString(),
                        ]
                    );
                }
            }
        });

        return back()->with('success', 'Validación profesional del cluster registrada exitosamente.');
    }

    /**
     * Valida la predicción específica de un paciente por parte del nutricionista.
     */
    public function validarPrediccion(Request $request, PrediccionMl $prediccion): RedirectResponse
    {
        $usuario = Auth::user();
        $nutricionista = $usuario?->nutricionista;

        $request->validate([
            'estado_validacion_pml' => ['required', 'in:validado_correcto,corregido,descartado'],
            'resultado_validado_pml' => ['nullable', 'string', 'max:100'],
            'obs_validacion_pml' => ['nullable', 'string', 'max:1000'],
        ]);

        $prediccion->update([
            'estado_validacion_pml' => $request->input('estado_validacion_pml'),
            'resultado_validado_pml' => $request->input('resultado_validado_pml') ?: $prediccion->resultado_principal_pml,
            'cod_nutricionistas_validador_pml' => $nutricionista?->cod_nutricionistas,
            'fec_validacion_pml' => now(),
            'obs_validacion_pml' => $request->input('obs_validacion_pml'),
        ]);

        return back()->with('success', 'Validación clínica de la inferencia registrada.');
    }
}
