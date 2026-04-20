<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $request->user() ? [
                    'cod_usuarios' => $request->user()->cod_usuarios,
                    'nom_usu' => $request->user()->nom_usu,
                    'cor_usu' => $request->user()->cor_usu,
                ] : null,
                'rol_activo' => fn () => session('nom_rol_activo'),
                'cod_rol_activo' => fn () => session('cod_rol_activo'),
                'roles_disponibles' => fn () => $request->user()
                    ? $request->user()->roles()
                        ->orderBy('nom_rol')
                        ->get(['roles.cod_roles', 'roles.nom_rol'])
                        ->map(fn ($rol) => [
                            'cod_roles' => $rol->cod_roles,
                            'nom_rol' => $rol->nom_rol,
                        ])
                    : [],
            ],
            'flash' => [
                'success' => fn () => session('success'),
                'error' => fn () => session('error'),
            ],
        ];
    }
}
