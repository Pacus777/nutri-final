<?php

namespace Tests\Unit\Services;

use App\Models\Alimento;
use App\Models\DetalleRegistroAlimenticio;
use App\Models\RegistroAlimenticio;
use App\Models\TablaComposicionNutricional;
use App\Services\CalculoNutricionalRegistroService;
use Illuminate\Support\Collection;
use RuntimeException;
use Tests\TestCase;

class CalculoNutricionalRegistroServiceTest extends TestCase
{
    public function test_validar_calculable_falla_si_registro_no_esta_cerrado_y_sin_detalles(): void
    {
        $service = new CalculoNutricionalRegistroService();

        $registro = new RegistroAlimenticio([
            'est_ral' => 'activo',
        ]);
        $registro->setRelation('detalles', new Collection());

        $resultado = $service->validarCalculable($registro, true);

        $this->assertFalse($resultado['valido']);

        $codigos = collect($resultado['errores'])->pluck('codigo')->all();

        $this->assertContains('registro_no_cerrado', $codigos);
        $this->assertContains('registro_sin_detalles', $codigos);
    }

    public function test_validar_calculable_falla_si_unidad_es_incompatible(): void
    {
        $service = new CalculoNutricionalRegistroService();

        $registro = new RegistroAlimenticio([
            'est_ral' => 'cerrado',
        ]);

        $detalle = new DetalleRegistroAlimenticio([
            'cod_detalle_registro_alimenticio' => 11,
            'cod_alimentos_dra' => 21,
            'can_dra' => 1,
            'uni_dra' => 'unidad',
        ]);

        $composicion = new TablaComposicionNutricional([
            'cant_ref_tcn' => 100,
            'uni_ref_tcn' => 'gramos',
            'est_tcn' => 'vigente',
        ]);

        $alimento = new Alimento([
            'nom_ali' => 'Manzana',
        ]);
        $alimento->setRelation('composicionVigente', $composicion);
        $detalle->setRelation('alimento', $alimento);

        $registro->setRelation('detalles', new Collection([$detalle]));

        $resultado = $service->validarCalculable($registro, true);

        $this->assertFalse($resultado['valido']);
        $this->assertSame('detalle_no_calculable', $resultado['errores'][0]['codigo']);
        $this->assertSame('unidad_incompatible', $resultado['errores'][0]['detalles'][0]['razon']);
    }

    public function test_calcular_totales_realiza_consolidado_escalado_por_cantidad(): void
    {
        $service = new CalculoNutricionalRegistroService();

        $registro = new RegistroAlimenticio([
            'est_ral' => 'cerrado',
        ]);

        $detalle = new DetalleRegistroAlimenticio([
            'cod_alimentos_dra' => 1,
            'can_dra' => 50,
            'uni_dra' => 'gramos',
        ]);

        $composicion = new TablaComposicionNutricional([
            'cant_ref_tcn' => 100,
            'uni_ref_tcn' => 'g',
            'cal_tcn' => 200,
            'pro_tcn' => 10,
            'gra_tcn' => 4,
            'car_tcn' => 30,
            'vit_a_tcn' => 2,
            'vit_c_tcn' => 4,
            'vit_d_tcn' => 6,
            'sod_tcn' => 20,
            'pot_tcn' => 30,
            'calcio_tcn' => 10,
            'hierro_tcn' => 4,
            'est_tcn' => 'vigente',
        ]);

        $alimento = new Alimento([
            'nom_ali' => 'Avena',
        ]);
        $alimento->setRelation('composicionVigente', $composicion);
        $detalle->setRelation('alimento', $alimento);

        $registro->setRelation('detalles', new Collection([$detalle]));

        $totales = $service->calcularTotales($registro, true);

        $this->assertSame(100.0, $totales['cal_cnr']);
        $this->assertSame(5.0, $totales['pro_cnr']);
        $this->assertSame(2.0, $totales['gra_cnr']);
        $this->assertSame(15.0, $totales['car_cnr']);
        $this->assertSame(6.0, $totales['vit_cnr']);
        $this->assertSame(32.0, $totales['min_cnr']);
    }

    public function test_validar_calculable_permire_registro_activo_si_es_de_fecha_anterior(): void
    {
        $service = new CalculoNutricionalRegistroService();

        $registro = new RegistroAlimenticio([
            'est_ral' => 'activo',
            'fec_ral' => now()->subDays(3)->toDateString(),
        ]);

        $detalle = new DetalleRegistroAlimenticio([
            'cod_detalle_registro_alimenticio' => 100,
            'cod_alimentos_dra' => 9,
            'can_dra' => 100,
            'uni_dra' => 'gramos',
        ]);

        $composicion = new TablaComposicionNutricional([
            'cant_ref_tcn' => 100,
            'uni_ref_tcn' => 'g',
            'cal_tcn' => 90,
            'pro_tcn' => 3,
            'gra_tcn' => 1,
            'car_tcn' => 12,
            'vit_a_tcn' => 0,
            'vit_c_tcn' => 0,
            'vit_d_tcn' => 0,
            'sod_tcn' => 0,
            'pot_tcn' => 0,
            'calcio_tcn' => 0,
            'hierro_tcn' => 0,
            'est_tcn' => 'vigente',
        ]);

        $alimento = new Alimento([
            'nom_ali' => 'Yogur',
        ]);
        $alimento->setRelation('composicionVigente', $composicion);
        $detalle->setRelation('alimento', $alimento);

        $registro->setRelation('detalles', new Collection([$detalle]));

        $resultado = $service->validarCalculable($registro, true);

        $this->assertTrue($resultado['valido']);
        $this->assertCount(0, $resultado['errores']);
    }

    public function test_calcular_totales_lanza_excepcion_si_total_resulta_negativo(): void
    {
        $service = new CalculoNutricionalRegistroService();

        $registro = new RegistroAlimenticio([
            'est_ral' => 'cerrado',
        ]);

        $detalle = new DetalleRegistroAlimenticio([
            'cod_alimentos_dra' => 1,
            'can_dra' => 100,
            'uni_dra' => 'gramos',
        ]);

        $composicion = new TablaComposicionNutricional([
            'cant_ref_tcn' => 100,
            'uni_ref_tcn' => 'gramos',
            'cal_tcn' => -10,
            'pro_tcn' => 0,
            'gra_tcn' => 0,
            'car_tcn' => 0,
            'vit_a_tcn' => 0,
            'vit_c_tcn' => 0,
            'vit_d_tcn' => 0,
            'sod_tcn' => 0,
            'pot_tcn' => 0,
            'calcio_tcn' => 0,
            'hierro_tcn' => 0,
            'est_tcn' => 'vigente',
        ]);

        $alimento = new Alimento([
            'nom_ali' => 'Dato inconsistente',
        ]);
        $alimento->setRelation('composicionVigente', $composicion);
        $detalle->setRelation('alimento', $alimento);

        $registro->setRelation('detalles', new Collection([$detalle]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no puede ser negativo');

        $service->calcularTotales($registro, true);
    }
}
