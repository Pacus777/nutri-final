<?php

namespace App\Http\Controllers;

use App\Models\Alimento;
use App\Models\Nutricionista;
use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BuscarGlobalController extends Controller
{
    public function buscar(Request $request): JsonResponse
    {
        $query = trim($request->input('q'));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $resultados = [];

        // 1. Buscar Pacientes (máximo 5)
        $pacientes = Paciente::query()
            ->with('usuario')
            ->whereHas('usuario', function ($q) use ($query) {
                $q->where('nom_usu', 'like', "%{$query}%")
                    ->orWhere('ape_usu', 'like', "%{$query}%")
                    ->orWhere('cor_usu', 'like', "%{$query}%")
                    ->orWhere('ci_usu', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get();

        foreach ($pacientes as $paciente) {
            $resultados[] = [
                'id' => 'paciente-' . $paciente->cod_pacientes,
                'categoria' => 'Paciente',
                'titulo' => $paciente->usuario?->nombre_completo ?? 'Paciente sin nombre',
                'subtitulo' => 'CI: ' . ($paciente->usuario?->ci_usu ?? 'N/D') . ' · ' . ($paciente->usuario?->cor_usu ?? ''),
                'url' => route('pacientes.show', $paciente->cod_pacientes),
                'color' => 'emerald',
            ];
        }

        // 2. Buscar Nutricionistas (máximo 5)
        $nutricionistas = Nutricionista::query()
            ->with('usuario')
            ->whereHas('usuario', function ($q) use ($query) {
                $q->where('nom_usu', 'like', "%{$query}%")
                    ->orWhere('ape_usu', 'like', "%{$query}%")
                    ->orWhere('cor_usu', 'like', "%{$query}%");
            })
            ->orWhere('esp_nut', 'like', "%{$query}%")
            ->limit(5)
            ->get();

        foreach ($nutricionistas as $nutricionista) {
            $resultados[] = [
                'id' => 'nutricionista-' . $nutricionista->cod_nutricionistas,
                'categoria' => 'Nutricionista',
                'titulo' => $nutricionista->usuario?->nombre_completo ?? 'Nutricionista sin nombre',
                'subtitulo' => ($nutricionista->esp_nut ?? 'Especialista') . ' · ' . ($nutricionista->usuario?->cor_usu ?? ''),
                'url' => route('nutricionistas.show', $nutricionista->cod_nutricionistas),
                'color' => 'sky',
            ];
        }

        // 3. Buscar Alimentos (máximo 5)
        $alimentos = Alimento::query()
            ->where('nom_ali', 'like', "%{$query}%")
            ->orWhere('nom_ext_ali', 'like', "%{$query}%")
            ->orWhere('mar_ali', 'like', "%{$query}%")
            ->limit(5)
            ->get();

        foreach ($alimentos as $alimento) {
            $resultados[] = [
                'id' => 'alimento-' . $alimento->cod_alimentos,
                'categoria' => 'Alimento',
                'titulo' => $alimento->nom_ali,
                'subtitulo' => ($alimento->mar_ali ?? 'Genérico') . ' · Categoría: ' . ($alimento->tip_ali ?? 'Comida'),
                'url' => route('alimentos.show', $alimento->cod_alimentos),
                'color' => 'violet',
            ];
        }

        return response()->json($resultados);
    }
}
