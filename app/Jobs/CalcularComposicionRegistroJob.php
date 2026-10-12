<?php

namespace App\Jobs;

use App\Models\RegistroAlimenticio;
use App\Services\CalculoNutricionalRegistroService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CalcularComposicionRegistroJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $registroId)
    {
    }

    public function handle(CalculoNutricionalRegistroService $service): void
    {
        $registro = RegistroAlimenticio::find($this->registroId);

        if (! $registro) {
            Log::warning("CalculoJob: registro {$this->registroId} no encontrado.");
            return;
        }

        DB::transaction(function () use ($registro, $service) {
            $registro = RegistroAlimenticio::query()
                ->where('cod_registros_alimenticios', $registro->cod_registros_alimenticios)
                ->lockForUpdate()
                ->first();

            if ($registro->composicionNutricionalRegistrada()->exists()) {
                return;
            }

            $validacion = $service->validarCalculable($registro, true);

            if (! $validacion['valido']) {
                Log::warning('CalculoJob: registro '.$registro->cod_registros_alimenticios.' no calculable: '.json_encode($validacion['errores']));
                return;
            }

            $service->calcularYGuardar($registro, true);
        });
    }

    public function failed(Throwable $exception): void
    {
        Log::error('CalculoJob falló para registro '.$this->registroId.': '.$exception->getMessage());
    }
}
