<?php

namespace App\Services;

use App\Models\AsignacionNutricionistaPaciente;
use App\Models\Nutricionista;
use App\Models\Paciente;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class NutricionistaPacienteAccessService
{
    public function nutricionistaAutenticado(): ?Nutricionista
    {
        $usuario = Auth::user();

        if (! $usuario) {
            return null;
        }

        return Nutricionista::query()
            ->where('cod_usuarios_nut', $usuario->getKey())
            ->where('est_nut', 'activo')
            ->first();
    }

    public function obtenerNutricionistaAutenticadoOrFail(): Nutricionista
    {
        $nutricionista = $this->nutricionistaAutenticado();

        if (! $nutricionista) {
            throw new AuthorizationException(
                'No se encontró un perfil de nutricionista activo para el usuario autenticado.'
            );
        }

        return $nutricionista;
    }

    public function asignacionActiva(
        Paciente $paciente,
        ?Nutricionista $nutricionista = null
    ): ?AsignacionNutricionistaPaciente {
        $nutricionista ??= $this->obtenerNutricionistaAutenticadoOrFail();

        return AsignacionNutricionistaPaciente::query()
            ->where('cod_nutricionistas_anp', $nutricionista->getKey())
            ->where('cod_pacientes_anp', $paciente->getKey())
            ->where('est_anp', 'activa')
            ->first();
    }

    public function puedeGestionarPaciente(Paciente $paciente): bool
    {
        $nutricionista = $this->nutricionistaAutenticado();

        if (! $nutricionista) {
            return false;
        }

        if (! $nutricionista->estaActivo()) {
            return false;
        }

        if (! $paciente->estaActivo()) {
            return false;
        }

        return $paciente->tieneAsignacionActivaCon($nutricionista->getKey());
    }

    public function asegurarAccesoAPaciente(Paciente $paciente): Nutricionista
    {
        $nutricionista = $this->obtenerNutricionistaAutenticadoOrFail();

        if (! $nutricionista->estaActivo()) {
            throw new AuthorizationException(
                'El perfil del nutricionista no se encuentra activo.'
            );
        }

        if (! $paciente->estaActivo()) {
            throw new AuthorizationException(
                'El paciente no se encuentra activo.'
            );
        }

        $tieneAsignacionActiva = $paciente->tieneAsignacionActivaCon(
            $nutricionista->getKey()
        );

        if (! $tieneAsignacionActiva) {
            throw new AuthorizationException(
                'No tienes una asignación activa con este paciente.'
            );
        }

        return $nutricionista;
    }

    public function pacientesAsignadosQuery(): Builder
    {
        $nutricionista = $this->obtenerNutricionistaAutenticadoOrFail();

        return Paciente::query()
            ->where('est_pac', 'activo')
            ->whereHas('asignaciones', function (Builder $query) use ($nutricionista) {
                $query->where('cod_nutricionistas_anp', $nutricionista->getKey())
                    ->where('est_anp', 'activa');
            })
            ->with([
                'usuario',
                'asignacionActiva.nutricionista.usuario',
            ]);
    }
}