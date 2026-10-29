<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Una clave nueva guarda su tipo: los textos no se leen como 0. */
class ConfiguracionTipoTest extends TestCase
{
    use RefreshDatabase;

    public function test_textos_numeros_y_ruc_conservan_su_valor(): void
    {
        Configuracion::set('planta_nombre', 'Planta ProLech Huata');
        Configuracion::set('planta_lat', '-15.633');
        Configuracion::set('capacidad_camion_litros', '1500');
        Configuracion::set('empresa_ruc', '20601234567');
        Configuracion::set('yape_numero', '951 234 567');

        $this->assertSame('Planta ProLech Huata', Configuracion::get('planta_nombre'));
        $this->assertSame(-15.633, Configuracion::get('planta_lat'));
        $this->assertSame(1500.0, Configuracion::get('capacidad_camion_litros'));
        $this->assertSame('20601234567', Configuracion::get('empresa_ruc'));
        $this->assertSame('951 234 567', Configuracion::get('yape_numero'));
    }

    public function test_actualizar_no_cambia_el_tipo_de_una_clave_existente(): void
    {
        $this->assertSame('decimal', Configuracion::where('clave', 'precio_litro_normal')->value('tipo'));
        Configuracion::set('precio_litro_normal', '1.80');
        $this->assertSame(1.8, Configuracion::get('precio_litro_normal'));
    }
}
