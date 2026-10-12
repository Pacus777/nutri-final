<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificacionController extends Controller
{
    public function obtenerNoLeidas(): JsonResponse
    {
        $usuario = Auth::user();

        if (!$usuario) {
            return response()->json([
                'notificaciones' => [],
                'total_no_leidas' => 0
            ]);
        }

        $notificaciones = Notificacion::query()
            ->where('cod_usuarios_not', $usuario->cod_usuarios)
            ->noLeidas()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (Notificacion $n) => [
                'cod_notificaciones' => $n->cod_notificaciones,
                'tit_not' => $n->tit_not,
                'des_not' => $n->des_not,
                'tip_not' => $n->tip_not,
                'url_not' => $n->url_not,
                'fec_not' => $n->created_at->diffForHumans(),
            ]);

        $totalNoLeidas = Notificacion::query()
            ->where('cod_usuarios_not', $usuario->cod_usuarios)
            ->noLeidas()
            ->count();

        return response()->json([
            'notificaciones' => $notificaciones,
            'total_no_leidas' => $totalNoLeidas,
        ]);
    }

    public function marcarLeida(int $id): JsonResponse
    {
        $usuario = Auth::user();
        $notificacion = Notificacion::find($id);

        if (!$notificacion) {
            return response()->json(['error' => 'Notificación no encontrada'], 404);
        }

        if (!$usuario || (int) $notificacion->cod_usuarios_not !== (int) $usuario->cod_usuarios) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $notificacion->update(['est_not' => 'leido']);

        return response()->json(['success' => true]);
    }

    public function marcarTodasLeidas(): JsonResponse
    {
        $usuario = Auth::user();

        if (!$usuario) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        Notificacion::query()
            ->where('cod_usuarios_not', $usuario->cod_usuarios)
            ->noLeidas()
            ->update(['est_not' => 'leido']);

        return response()->json(['success' => true]);
    }

    public function obtenerTodas(): JsonResponse
    {
        $usuario = Auth::user();

        if (!$usuario) {
            return response()->json(['notificaciones' => []]);
        }

        $notificaciones = Notificacion::query()
            ->where('cod_usuarios_not', $usuario->cod_usuarios)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Notificacion $n) => [
                'cod_notificaciones' => $n->cod_notificaciones,
                'tit_not' => $n->tit_not,
                'des_not' => $n->des_not,
                'tip_not' => $n->tip_not,
                'url_not' => $n->url_not,
                'est_not' => $n->est_not,
                'fec_not' => $n->created_at->diffForHumans(),
                'fec_full' => $n->created_at->format('d/m/Y H:i'),
            ]);

        return response()->json([
            'notificaciones' => $notificaciones,
        ]);
    }

    public function alternarEstado(int $id): JsonResponse
    {
        $usuario = Auth::user();
        $notificacion = Notificacion::find($id);

        if (!$notificacion) {
            return response()->json(['error' => 'Notificación no encontrada'], 404);
        }

        if (!$usuario || (int) $notificacion->cod_usuarios_not !== (int) $usuario->cod_usuarios) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $nuevoEstado = $notificacion->est_not === 'leido' ? 'no_leido' : 'leido';
        $notificacion->update(['est_not' => $nuevoEstado]);

        return response()->json([
            'success' => true,
            'est_not' => $nuevoEstado
        ]);
    }

    public function obtenerRecomendaciones(): JsonResponse
    {
        $usuario = Auth::user();

        if (!$usuario) {
            return response()->json([
                'recomendaciones' => []
            ]);
        }

        $paciente = \App\Models\Paciente::where('cod_usuarios_pac', $usuario->cod_usuarios)->first();

        if (!$paciente) {
            return response()->json([
                'recomendaciones' => []
            ]);
        }

        $recomendaciones = $paciente->seguimientosNutricionales()
            ->with('nutricionista.usuario')
            ->orderByDesc('fec_seg')
            ->orderByDesc('cod_seguimientos_nutricionales')
            ->limit(5)
            ->get()
            ->map(fn ($s) => [
                'cod_seguimientos_nutricionales' => $s->cod_seguimientos_nutricionales,
                'fecha' => optional($s->fec_seg)->format('Y-m-d'),
                'nutricionista' => $s->nutricionista?->usuario?->nombre_completo ?? 'Nutricionista',
                'recomendaciones' => $s->res_seg,
                'cambios_sugeridos' => $s->cam_seg,
            ]);

        return response()->json([
            'recomendaciones' => $recomendaciones,
        ]);
    }
}

