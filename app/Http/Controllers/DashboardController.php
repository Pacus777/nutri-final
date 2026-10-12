<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardAdminService;
use App\Services\Dashboard\DashboardNutricionistaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    protected DashboardNutricionistaService $dashboardNutricionistaService;
    protected DashboardAdminService $dashboardAdminService;

    public function __construct(
        DashboardNutricionistaService $dashboardNutricionistaService,
        DashboardAdminService $dashboardAdminService
    ) {
        $this->dashboardNutricionistaService = $dashboardNutricionistaService;
        $this->dashboardAdminService = $dashboardAdminService;
    }

    /**
     * Detecta el rol activo del usuario y delega la renderización del Dashboard al servicio correspondiente.
     */
    public function index(): Response|RedirectResponse
    {
        $usuario = Auth::user();
        $rolActivo = trim((string) session('nom_rol_activo', session('rol_activo')));

        // Caso: Paciente
        if ($rolActivo === 'Paciente') {
            return redirect()->route('dashboard.paciente');
        }

        // Caso: Nutricionista
        if ($rolActivo === 'Nutricionista') {
            $datos = $this->dashboardNutricionistaService->getDatos($usuario);
            return Inertia::render('Dashboard/Nutricionista', $datos);
        }

        // Caso: Administrador o SuperAdmin
        if (in_array($rolActivo, ['SuperAdmin', 'Administrador'], true)) {
            $datos = $this->dashboardAdminService->getDatos();
            return Inertia::render('Dashboard', $datos);
        }

        // Fallback: Selección de rol si no hay uno activo en sesión
        session()->forget(['cod_rol_activo', 'nom_rol_activo', 'rol_activo']);

        return Inertia::render('Auth/SeleccionarRol', [
            'roles' => $usuario->roles()
                ->orderBy('nom_rol')
                ->get(['roles.cod_roles', 'roles.nom_rol'])
                ->map(fn ($rol) => [
                    'cod_roles' => $rol->cod_roles,
                    'nom_rol' => trim((string) $rol->nom_rol),
                ]),
            'rolActivo' => null,
        ]);
    }
}