<?php

namespace App\Http\Controllers\Nutricionista;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Models\RecomendacionNutricional;
use App\Services\MotorAnalisisNutricionalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecomendacionNutricionalController extends Controller
{
    public function __construct(
        protected MotorAnalisisNutricionalService $motorService
    ) {}

    public function aprobar(Request $request, RecomendacionNutricional $recomendacion): RedirectResponse
    {
        $nutricionista = Auth::user()?->nutricionista;
        if (!$nutricionista) {
            return back()->withErrors(['general' => 'No se identificó el perfil de nutricionista.']);
        }

        $desEditada = $request->input('des_rec');
        $this->motorService->aprobarRecomendacion($recomendacion, $nutricionista, $desEditada);

        return back()->with('success', 'Recomendación aprobada e incorporada al expediente clínico.');
    }

    public function descartar(Request $request, RecomendacionNutricional $recomendacion): RedirectResponse
    {
        $nutricionista = Auth::user()?->nutricionista;
        if (!$nutricionista) {
            return back()->withErrors(['general' => 'No se identificó el perfil de nutricionista.']);
        }

        $this->motorService->descartarRecomendacion($recomendacion, $nutricionista);

        return back()->with('success', 'Sugerencia asistida descartada correctamente.');
    }

    public function storeManual(Request $request, Paciente $paciente): RedirectResponse
    {
        $nutricionista = Auth::user()?->nutricionista;
        if (!$nutricionista) {
            return back()->withErrors(['general' => 'No se identificó el perfil de nutricionista.']);
        }

        $request->validate([
            'des_rec' => ['required', 'string', 'min:5', 'max:1000'],
            'tip_rec' => ['nullable', 'string', 'max:80'],
            'categoria_rec' => ['nullable', 'string', 'max:60'],
        ]);

        $this->motorService->crearRecomendacionManual(
            $paciente,
            $nutricionista,
            $request->input('des_rec'),
            $request->input('tip_rec', 'indicacion_clinica'),
            $request->input('categoria_rec', 'criterio_profesional')
        );

        return back()->with('success', 'Recomendación clínica registrada exitosamente.');
    }

    public function reanalizarMotor(Paciente $paciente): RedirectResponse
    {
        $this->motorService->orquestarAnalisisCompleto($paciente);
        return back()->with('success', 'Motor Inteligente Nutricional ejecutado exitosamente.');
    }
}
