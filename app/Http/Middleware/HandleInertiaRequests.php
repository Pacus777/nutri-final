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
                'user' => fn () => auth()->user() ? [
                    'cod_usuarios' => auth()->user()->cod_usuarios,
                    'nom_usu' => auth()->user()->nom_usu,
                    'cor_usu' => auth()->user()->cor_usu,
                ] : null,
                'rol_activo' => fn () => session('nom_rol_activo'),
                'cod_rol_activo' => fn () => session('cod_rol_activo'),
            ],
        ];

        
    }
}
